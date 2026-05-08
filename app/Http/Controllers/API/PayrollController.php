<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    // =========================================================================
    // Teacher endpoints — guru lihat slip sendiri
    // =========================================================================

    /**
     * GET /api/v1/payroll/me?month=5&year=2026
     * Slip gaji guru yang sedang login untuk bulan tertentu.
     */
    public function mySlip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'  => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year']  ?? now()->year;

        $salary = Salary::byUser($request->user()->id)
            ->byMonth($month, $year)
            ->with('user.teacher')
            ->first();

        if (!$salary) {
            return $this->success('Slip gaji belum tersedia untuk periode ini.', ['salary' => null]);
        }

        return $this->success('Slip gaji berhasil diambil.', [
            'salary' => $this->payrollService->formatSalary($salary, withTeacher: false),
        ]);
    }

    /**
     * GET /api/v1/payroll/history?year=2026
     * Histori slip gaji guru per tahun.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $year = $validated['year'] ?? now()->year;

        $salaries = Salary::byUser($request->user()->id)
            ->where('year', $year)
            ->orderBy('month', 'desc')
            ->get()
            ->map(fn(Salary $s) => $this->payrollService->formatSalary($s, withTeacher: false));

        return $this->success('Histori gaji berhasil diambil.', [
            'year'     => $year,
            'salaries' => $salaries,
        ]);
    }

    // =========================================================================
    // Admin endpoints — kelola payroll semua guru
    // =========================================================================

    /**
     * GET /api/v1/admin/payroll?month=5&year=2026&status=draft
     * Daftar slip gaji semua guru dengan filter.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month'    => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'     => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'status'   => ['nullable', 'string', 'in:draft,approved,paid'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year']  ?? now()->year;

        $query = Salary::byMonth($month, $year)
            ->with('user.teacher')
            ->orderBy('created_at', 'desc');

        if (!empty($validated['status'])) {
            $query->status($validated['status']);
        }

        $paginator = $query->paginate($validated['per_page'] ?? 20);

        $items = collect($paginator->items())
            ->map(fn(Salary $s) => $this->payrollService->formatSalary($s))
            ->values();

        // Agregat ringkasan halaman
        $allForMonth = Salary::byMonth($month, $year)->get();
        $aggregate = [
            'total_base_salary'  => $allForMonth->sum('base_salary'),
            'total_deductions'   => $allForMonth->sum(fn($s) => $s->total_deduction),
            'total_net_salary'   => $allForMonth->sum('total_salary'),
            'count_draft'        => $allForMonth->where('status', 'draft')->count(),
            'count_approved'     => $allForMonth->where('status', 'approved')->count(),
            'count_paid'         => $allForMonth->where('status', 'paid')->count(),
        ];

        return $this->success('Daftar payroll berhasil diambil.', [
            'month'      => $month,
            'year'       => $year,
            'aggregate'  => $aggregate,
            'items'      => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/payroll/{salary}
     * Detail satu slip gaji.
     */
    public function show(Salary $salary): JsonResponse
    {
        $salary->load('user.teacher');

        return $this->success('Detail slip gaji berhasil diambil.', [
            'salary' => $this->payrollService->formatSalary($salary),
        ]);
    }

    /**
     * POST /api/v1/admin/payroll/generate
     * Hitung payroll untuk semua guru (atau satu guru) dalam satu bulan.
     *
     * Body:
     *   month      required  int
     *   year       required  int
     *   teacher_id optional  int — kalau kosong: generate semua guru
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2099'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            if (!empty($validated['teacher_id'])) {
                $teacher = User::findOrFail($validated['teacher_id']);

                if (!$teacher->isTeacher()) {
                    return $this->error('User ini bukan guru.', null, 422);
                }

                $salary = $this->payrollService->calculate(
                    $teacher,
                    $validated['month'],
                    $validated['year']
                );

                return $this->success('Payroll berhasil dikalkulasi.', [
                    'salary' => $this->payrollService->formatSalary($salary),
                ], 201);
            }

            // Generate semua guru
            $results = $this->payrollService->generateAll($validated['month'], $validated['year']);

            return $this->success(
                sprintf(
                    'Payroll berhasil: %d dikalkulasi, %d dilewati.',
                    count($results['generated']),
                    count($results['skipped'])
                ),
                $results,
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * POST /api/v1/admin/payroll/{salary}/approve
     * Setujui slip gaji (draft → approved).
     *
     * Body:
     *   notes  optional  string
     */
    public function approve(Salary $salary, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $salary = $this->payrollService->approve($salary, $validated['notes'] ?? null);

            return $this->success('Slip gaji berhasil disetujui.', [
                'salary' => $this->payrollService->formatSalary($salary),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * POST /api/v1/admin/payroll/{salary}/paid
     * Tandai slip gaji sudah dibayar (approved → paid).
     *
     * Body:
     *   notes  optional  string
     */
    public function markAsPaid(Salary $salary, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $salary = $this->payrollService->markAsPaid($salary, $validated['notes'] ?? null);

            return $this->success('Slip gaji ditandai sudah dibayar.', [
                'salary' => $this->payrollService->formatSalary($salary),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * POST /api/v1/admin/payroll/{salary}/revert
     * Kembalikan ke draft untuk koreksi (approved → draft).
     */
    public function revert(Salary $salary): JsonResponse
    {
        try {
            $salary = $this->payrollService->revertToDraft($salary);

            return $this->success('Slip gaji dikembalikan ke status draft.', [
                'salary' => $this->payrollService->formatSalary($salary),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $status);
    }

    private function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => [],
            'errors'  => $errors,
        ], $status);
    }
}

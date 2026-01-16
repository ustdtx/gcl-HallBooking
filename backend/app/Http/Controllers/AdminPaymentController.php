<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use PDF;

class AdminPaymentController extends Controller
{
    private function applyFilters($query, $request)
    {
        if ($request->booking_id) {
            $query->where('booking_id', $request->booking_id);
        }
        if ($request->club_account) {
            $query->whereHas('booking.member', function($q) use ($request) {
                $q->where('club_account', 'like', '%' . $request->club_account . '%');
            });
        }
        if ($request->tran_id) {
            $query->where('tran_id', 'like', '%' . $request->tran_id . '%');
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['booking.member', 'booking.hall'])
            ->where('status', 'success');
        $query = $this->applyFilters($query, $request);
        $perPage = $request->per_page == 'all' ? $query->count() : ($request->per_page ?? 25);
        $payments = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $filteredPayments = $query->get();
        $totalPayments = $filteredPayments->count();
        $totalRevenue = $filteredPayments->sum('amount');
        $preBookAmount = $filteredPayments->where('type', 'Pre-Book')->sum('amount');
        $finalPaymentAmount = $filteredPayments->where('type', 'Final')->sum('amount');
        $hallRevenue = $filteredPayments->groupBy(function($item){
            return optional($item->booking->hall)->name;
        })->map->sum('amount');
        // Also pass the type values for debugging/clarity
        $typeValues = $filteredPayments->pluck('type')->unique();
        return view('admin.payments', compact(
            'payments', 'totalPayments', 'totalRevenue', 'preBookAmount', 'finalPaymentAmount', 'hallRevenue'
        ));
    }

    public function downloadReport(Request $request)
    {
        $query = Payment::with(['booking.member', 'booking.hall'])
            ->where('status', 'success');
        $query = $this->applyFilters($query, $request);
        $payments = $query->get();
        $totalBookings = $payments->unique('booking_id')->count();
        $totalRevenue = $payments->sum('amount');
        $preBookAmount = $payments->where('type', 'Pre-Book')->sum('amount');
        $finalPaymentAmount = $payments->where('type', 'Final')->sum('amount');
        $hallRevenue = $payments->groupBy(function($item){
            return optional($item->booking->hall)->name;
        })->map->sum('amount');
        $pdf = PDF::loadView('admin.payments_report_pdf', compact(
            'payments', 'totalBookings', 'totalRevenue', 'preBookAmount', 'finalPaymentAmount', 'hallRevenue'
        ));
        return $pdf->download('payment_report.pdf');
    }

    public function downloadInvoice(Payment $payment)
    {
        $payment->load(['booking.member', 'booking.hall']);
        $pdf = PDF::loadView('admin.payment_invoice_pdf', compact('payment'));
        return $pdf->download('invoice_' . $payment->tran_id . '.pdf');
    }
}

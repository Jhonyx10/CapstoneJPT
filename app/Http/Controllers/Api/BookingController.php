<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BookingService;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index()
    {
        $bookings = $this->bookingService->getAll();
        return response()->json($bookings);
    }

    public function show($id)
    {
        $booking = $this->bookingService->getBookingById($id);
        return response()->json($booking);
    }

    public function getCustomerBookings($customerId)
    {
        $bookings = $this->bookingService->getCustomerBookings($customerId);
        return response()->json($bookings);
    }
}

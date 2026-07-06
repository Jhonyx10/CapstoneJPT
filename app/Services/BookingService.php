<?php

namespace App\Services;

use App\Models\Booking;

class BookingService
{
    public function getAll()
    {
        return Booking::all();
    }

    public function getBookingById($id)
    {
        return Booking::with('vehicle', 'customer')->find($id);
    }

    public function getCustomerBookings($customerId)
    {
        return Booking::with('vehicle', 'customer')->where('customer_id', $customerId)->get();
    }
}
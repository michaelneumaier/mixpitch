<?php

namespace App\Http\Controllers;

use App\Services\PricingService;

class PricingController extends Controller
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function index()
    {
        $plans = $this->pricingService->getAllPlansForPricing();
        $yearlyDiscount = $this->pricingService->getYearlyDiscountPercentage();

        return view('pricing', compact('plans', 'yearlyDiscount'));
    }
}

<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\CheckoutController;
use Modules\Subscription\Http\Controllers\CouponController;
use Modules\Subscription\Http\Controllers\DiscountController;
use Modules\Subscription\Http\Controllers\FeatureController;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\PlanFeatureController;
use Modules\Subscription\Http\Controllers\PublicPlanController;
use Modules\Subscription\Http\Controllers\UserSubscriptionController;
use Modules\Subscription\Http\Controllers\WebhookController;

Route::prefix('api')->group(function () {

    Route::post('stripe/webhook', [WebhookController::class, 'handleStripe'])
        ->name('api.stripe.webhook');

    Route::get('plans', [PublicPlanController::class, 'index'])
        ->name('api.plans.public.index');
    Route::get('plans/{id}', [PublicPlanController::class, 'show'])
        ->name('api.plans.public.show');

    Route::post('coupons/validate', [CouponController::class, 'validateCoupon'])
        ->name('api.coupons.validate');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('checkout', [CheckoutController::class, 'checkout'])
            ->name('api.checkout');

        Route::prefix('subscription')->group(function () {
            Route::get('current', [UserSubscriptionController::class, 'current'])
                ->name('api.subscription.current');
            Route::get('history', [UserSubscriptionController::class, 'history'])
                ->name('api.subscription.history');
            Route::post('cancel', [UserSubscriptionController::class, 'cancel'])
                ->name('api.subscription.cancel');
            Route::get('features', [UserSubscriptionController::class, 'featureAccess'])
                ->name('api.subscription.features');
            Route::get('features/{slug}', [UserSubscriptionController::class, 'checkFeature'])
                ->name('api.subscription.check-feature');
            Route::get('{id}/status-logs', [UserSubscriptionController::class, 'statusLogs'])
                ->name('api.subscription.status-logs');
        });

        Route::prefix('admin/subscription')->group(function () {
            Route::get('plans', [PlanController::class, 'index'])
                ->name('api.admin.subscription-plans.index');
            Route::post('plans', [PlanController::class, 'store'])
                ->name('api.admin.subscription-plans.store');
            Route::get('plans/{id}', [PlanController::class, 'show'])
                ->name('api.admin.subscription-plans.show');
            Route::patch('plans/{id}', [PlanController::class, 'update'])
                ->name('api.admin.subscription-plans.update');
            Route::delete('plans/{id}', [PlanController::class, 'destroy'])
                ->name('api.admin.subscription-plans.destroy');
            Route::post('plans/{id}/features', [PlanController::class, 'syncFeatures'])
                ->name('api.admin.subscription-plans.sync-features');

            Route::get('features', [FeatureController::class, 'index'])
                ->name('api.admin.subscription-features.index');
            Route::post('features', [FeatureController::class, 'store'])
                ->name('api.admin.subscription-features.store');
            Route::get('features/{id}', [FeatureController::class, 'show'])
                ->name('api.admin.subscription-features.show');
            Route::patch('features/{id}', [FeatureController::class, 'update'])
                ->name('api.admin.subscription-features.update');
            Route::delete('features/{id}', [FeatureController::class, 'destroy'])
                ->name('api.admin.subscription-features.destroy');

            Route::post('plan-features', [PlanFeatureController::class, 'store'])
                ->name('api.admin.subscription-plan-features.store');
            Route::patch('plan-features/{id}', [PlanFeatureController::class, 'update'])
                ->name('api.admin.subscription-plan-features.update');
            Route::delete('plan-features/{id}', [PlanFeatureController::class, 'destroy'])
                ->name('api.admin.subscription-plan-features.destroy');

            Route::get('discounts', [DiscountController::class, 'index'])
                ->name('api.admin.subscription-discounts.index');
            Route::post('discounts', [DiscountController::class, 'store'])
                ->name('api.admin.subscription-discounts.store');
            Route::get('discounts/{id}', [DiscountController::class, 'show'])
                ->name('api.admin.subscription-discounts.show');
            Route::patch('discounts/{id}', [DiscountController::class, 'update'])
                ->name('api.admin.subscription-discounts.update');
            Route::delete('discounts/{id}', [DiscountController::class, 'destroy'])
                ->name('api.admin.subscription-discounts.destroy');
        });
    });
});

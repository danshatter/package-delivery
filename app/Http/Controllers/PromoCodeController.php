<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\PromoCode;
use App\Rules\{ValidPromoCodeType, ValidPromoCodeExpirationDate, ValidPromoCodeValue};

class PromoCodeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the promo codes
     */
    public function index()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $promoCodes = PromoCode::latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $promoCodes);
    }

    /**
     * Create a promo code
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'code' => ['required', 'alpha_num', 'unique:promo_codes'],
            'type' => ['required', new ValidPromoCodeType],
            'value' => ['required', 'numeric', new ValidPromoCodeValue],
            'value_type' => ['required', Rule::in(['percentage', 'amount'])],
            'expires_at' => ['required', 'bail', 'date_format:Y-m-d', new ValidPromoCodeExpirationDate]
        ], [
            'value_type.in' => 'The :attribute should be either "percentage" or "amount"'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // The data that will be stored the database
        $data = $validator->validated();

        // If the promo code is restricted, then we store the record of it being used
        if ($data['type'] === PromoCode::RESTRICTED_CODE) {
            $data['is_used'] = false;
        }

        // Store the promo code
        $promoCode = PromoCode::create($data);

        return $this->sendSuccess('Promo Code added successfully', $promoCode, 201);
    }

    /**
     * Get a promo code
     */
    public function show($promoCodeId)
    {
        $promoCode = PromoCode::find($promoCodeId);

        if (is_null($promoCode)) {
            return $this->sendErrorMessage('Promo code not found', 404);
        }

        return $this->sendSuccess('Request successful', $promoCode);
    }

    /**
     * Delete a promo code
     */
    public function destroy($promoCodeId)
    {
        $promoCode = PromoCode::find($promoCodeId);

        if (is_null($promoCode)) {
            return $this->sendErrorMessage('Promo code not found', 404);
        }

        $promoCode->delete();

        return $this->sendSuccess('Promo code deleted successfully');
    }

}

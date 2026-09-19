<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_pocket_id' => [
                'required',
                'integer',

                Rule::exists('pockets', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'user_id',
                                $this->user()->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                    ),
            ],

            'to_pocket_id' => [
                'required',
                'integer',
                'different:from_pocket_id',

                Rule::exists('pockets', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'user_id',
                                $this->user()->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                    ),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:9999999999999999.99',
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'occurred_at' => [
                'required',
                'date',
            ],
        ];
    }
}
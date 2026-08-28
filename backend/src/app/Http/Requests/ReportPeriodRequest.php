<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    'monthly',
                    'quarterly',
                    'semiannual',
                    'annual',
                    'custom',
                ]),
            ],

            'year' => [
                'required_unless:type,custom',
                'nullable',
                'integer',
                'between:2000,2100',
            ],

            'month' => [
                'required_if:type,monthly',
                'nullable',
                'integer',
                'between:1,12',
            ],

            'quarter' => [
                'required_if:type,quarterly',
                'nullable',
                'integer',
                'between:1,4',
            ],

            'semester' => [
                'required_if:type,semiannual',
                'nullable',
                'integer',
                'between:1,2',
            ],

            'from' => [
                'required_if:type,custom',
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'required_if:type,custom',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],

            'pocket_id' => [
                'nullable',
                'integer',

                Rule::exists('pockets', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    ),
            ],
        ];
    }
}
<?php

namespace Modules\ZoneManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ZoneStoreUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->id;
        return [
            'name' => 'required|max:191|unique:zones,name,'.$id,
            'coordinates' => [
                'required',
                function ($attribute, $value, $fail) {
                    $count = preg_match_all('/\([^)]+\)/', (string) $value, $matches);
                    if ($count < 3) {
                        $fail(translate('click_this_icon_to_start_pin_points_in_the_map_and_connect_the_dots_together_to_draw_a_zone_._Minimum_3_points_required'));
                    }
                },
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }
}

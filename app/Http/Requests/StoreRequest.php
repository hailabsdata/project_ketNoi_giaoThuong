<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->route('store') ? $this->route('store')->id : null;

        return [
            'name' => 'required|string|max:255|unique:stores,name,' . $storeId,
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:stores,email,' . $storeId,
            'phone' => 'required|string|size:10|regex:/^[0-9]+$/',
            'address' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên cửa hàng là bắt buộc.',
            'name.unique' => 'Tên cửa hàng đã tồn tại.',
            'owner_name.required' => 'Tên chủ cửa hàng là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.size' => 'Số điện thoại phải có 10 ký tự.',
            'phone.regex' => 'Số điện thoại chỉ được chứa ký tự số.',
            'address.required' => 'Địa chỉ là bắt buộc.',
        ];
    }
}
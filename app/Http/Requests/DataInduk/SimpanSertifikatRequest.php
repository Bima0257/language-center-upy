<?php

namespace App\Http\Requests\DataInduk;

use Illuminate\Foundation\Http\FormRequest;

class SimpanSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sesi_ujian_id' => ['required', 'uuid', 'exists:sesi_ujian,id'],
            'nomor_sertifikat' => ['required', 'string', 'max:50', 'unique:sertifikat,nomor_sertifikat'],
            'diterbitkan_pada' => ['required', 'date'],
            'berlaku_hingga' => ['required', 'date', 'after:diterbitkan_pada'],
        ];
    }
}

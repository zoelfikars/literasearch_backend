<x-mail::message>
    Harap jangan berikan Kode OTP ini kepada siapapun!

    Kode OTP anda adalah : {{ $otp }}

    Kode ini hanya berlaku selama 5 menit.
    {{-- <x-mail::button :url="''">
        Button Text
    </x-mail::button> --}}
    Terimakasih,
    {{ config('app.name') }}
</x-mail::message>

@extends("layouts.EmailTemplateLayout")

@section("pageTitle", "Email validation link - {{ $SiteTitle }}")

@section("content")
<p style="text-transform: capitalize;"><b>Dear {{ $firstName }},</b></p>

<p>Greetings from {{ $SiteTitle }} team. Thank you for signing up with us.</p>
<p>To validate your email, please <a target="_blank" href="{{ $validationLink }}">click here</a> </p>
<p><b>OR </b></p>
<p>Copy and paste the following link into your browser:</p>
<p><a target="_blank" href="{{ $validationLink }}"> {{ $validationLink }} </a></p>
@endsection

@section("footer")
@include('layouts.EmailTemplateFooter')
@endsection
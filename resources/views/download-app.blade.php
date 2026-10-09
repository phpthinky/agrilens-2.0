@extends('layouts.public')
@section('title', 'Download Technician App')

@section('content')
<div class="container py-5" style="max-width: 640px;">
    <div class="text-center mb-4">
        <i class="fa fa-mobile-screen-button fa-3x text-success mb-3"></i>
        <h2 class="fw-bold">Agrilens Field — Technician App</h2>
        <p class="text-muted">
            An Android app for reading the handheld soil probe over USB-OTG in the field.
            Log in with your existing Agrilens account, see the farms scheduled for you to
            test, and submit a reading without needing a laptop.
        </p>
    </div>

    <div class="card mb-4">
        <div class="card-body text-center">
            @if($exists)
                <a href="{{ asset('downloads/agrilens-field-technician-app.apk') }}" class="btn btn-success btn-lg px-4" download>
                    <i class="fa fa-download me-2"></i>Download APK @if($sizeMb)({{ $sizeMb }} MB)@endif
                </a>
                <p class="text-muted small mt-2 mb-0">Android 7.0 (Nougat) or newer. Not available on the Play Store — this installs directly from this file.</p>
            @else
                <p class="text-danger mb-0"><i class="fa fa-triangle-exclamation me-1"></i>The APK hasn't been uploaded to the server yet.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Installation steps</strong></div>
        <div class="card-body">
            <ol class="mb-0">
                <li class="mb-2">Tap <strong>Download APK</strong> above using your phone's browser.</li>
                <li class="mb-2">Open the downloaded file. If prompted, allow your browser to <strong>install unknown apps</strong> — this is expected for an app installed outside the Play Store.</li>
                <li class="mb-2">Once installed, open <strong>Agrilens Field</strong> and log in with the same username and password you use on this website.</li>
                <li class="mb-2">Plug in the soil probe via a USB-OTG cable. If your phone has a separate "OTG" toggle in Settings, make sure it's turned on.</li>
                <li>Tap a farm from your list, connect the probe, read, review, and save.</li>
            </ol>
        </div>
    </div>
</div>
@endsection

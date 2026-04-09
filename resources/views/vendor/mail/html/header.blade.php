@props(['url'])
<!DOCTYPE html>
<html>
  <head></head>
  <body style="margin:0;padding:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background-color:#f8fafc;">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
      <tr>
        <td align="center" style="padding:24px 0;">
          <a href="{{ $url ?? config('app.url') }}" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit;">
            @if (file_exists(public_path('logo.png')))
              <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="height:48px;display:block;" />
            @else
              <div style="font-weight:700;font-size:20px;color:#0f172a;">{{ config('app.name') }}</div>
            @endif
          </a>
        </td>
      </tr>
      <tr>
        <td>
          <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.06);">
            <tr>
              <td style="padding:28px;">
                <!-- content will be injected -->

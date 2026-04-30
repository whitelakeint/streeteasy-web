<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; margin: 0; padding: 0; background: #f8fafc; }
    .container { max-width: 640px; margin: 0 auto; padding: 32px 24px; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
    h1 { font-size: 20px; font-weight: 600; margin: 0 0 4px; }
    h2 { font-size: 14px; font-weight: 600; margin: 0 0 12px; color: #334155; }
    .subtitle { font-size: 13px; color: #64748b; margin-bottom: 24px; }
    .stats { display: flex; gap: 12px; margin-bottom: 20px; }
    .stat { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center; }
    .stat-value { font-size: 28px; font-weight: 700; color: #0f172a; }
    .stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
    .stat-green .stat-value { color: #16a34a; }
    .stat-red .stat-value { color: #dc2626; }
    .stat-amber .stat-value { color: #d97706; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
    td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    .badge { display: inline-block; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 9999px; }
    .badge-green { background: #f0fdf4; color: #16a34a; }
    .badge-red { background: #fef2f2; color: #dc2626; }
    .badge-slate { background: #f1f5f9; color: #64748b; }
    .error-row { background: #fef2f2; border-left: 3px solid #dc2626; padding: 8px 12px; margin-bottom: 6px; border-radius: 6px; font-size: 13px; }
    .error-event { font-weight: 600; color: #dc2626; }
    .error-msg { color: #64748b; margin-top: 2px; }
    .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>StreetEasy Scrape Report</h1>
      <div class="subtitle">{{ $report['date'] }}</div>

      <div class="stats">
        <div class="stat stat-green">
          <div class="stat-value">{{ $report['completed'] }}</div>
          <div class="stat-label">Completed</div>
        </div>
        <div class="stat stat-red">
          <div class="stat-value">{{ $report['failed'] }}</div>
          <div class="stat-label">Failed</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ $report['properties_scraped'] }}</div>
          <div class="stat-label">Properties</div>
        </div>
        <div class="stat">
          <div class="stat-value">{{ $report['buildings_scraped'] }}</div>
          <div class="stat-label">Buildings</div>
        </div>
      </div>

      @if($report['warning_count'] > 0)
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #92400e;">
          {{ $report['warning_count'] }} warning(s) logged today
        </div>
      @endif
    </div>

    @if(count($report['errors']) > 0)
      <div class="card">
        <h2>Errors ({{ count($report['errors']) }})</h2>
        @foreach($report['errors'] as $err)
          <div class="error-row">
            <div class="error-event">{{ $err['event'] }}</div>
            <div class="error-msg">{{ $err['message'] }}</div>
          </div>
        @endforeach
      </div>
    @endif

    <div class="card">
      <h2>URL Status Breakdown</h2>
      <table>
        <thead>
          <tr>
            <th>Building</th>
            <th>Status</th>
            <th>Last Scraped</th>
          </tr>
        </thead>
        <tbody>
          @foreach($report['url_details'] as $url)
            <tr>
              <td style="font-weight: 500;">{{ $url['name'] }}</td>
              <td>
                @php
                  $badgeClass = match($url['status']) {
                    'completed' => 'badge-green',
                    'failed' => 'badge-red',
                    default => 'badge-slate',
                  };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $url['status'])) }}</span>
              </td>
              <td style="color: #64748b; font-size: 12px;">{{ $url['scraped_at'] ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="footer">
      Sent by StreetEasy Admin · {{ now()->format('m-d-Y H:i:s') }} EST
    </div>
  </div>
</body>
</html>

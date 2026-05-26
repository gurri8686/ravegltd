<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice #{{ $data['invoice']['id'] }}</title>
</head>
<body style="margin:0;padding:0;background:#eef0f3;font-family:'Segoe UI',Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f3;padding:32px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(15,17,21,0.08);">

                {{-- Brand header --}}
                <tr>
                    <td style="background:#ea580c;padding:26px 32px;">
                        <div style="font-size:21px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">{{ $data['company_name'] }}</div>
                        <div style="font-size:12px;color:#ffdcc4;margin-top:4px;letter-spacing:0.3px;">SALES INVOICE</div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:32px 32px 8px;">
                        <p style="font-size:13.5px;color:#374151;line-height:1.65;margin:0 0 22px;white-space:pre-line;">{{ $data['message'] }}</p>

                        {{-- Attachment notice --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;margin:0 0 8px;">
                            <tr>
                                <td style="padding:13px 16px;vertical-align:middle;width:34px;">
                                    <span style="display:inline-block;width:30px;height:30px;background:#ea580c;border-radius:7px;text-align:center;line-height:30px;color:#ffffff;font-size:14px;">&#128206;</span>
                                </td>
                                <td style="padding:13px 16px 13px 0;vertical-align:middle;">
                                    <div style="font-size:12.5px;font-weight:700;color:#9a3412;">Invoice attached (Excel)</div>
                                    <div style="font-size:11.5px;color:#c2671f;margin-top:2px;">Open the attachment to view the full invoice with all items.</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Sign-off --}}
                <tr>
                    <td style="padding:14px 32px 28px;">
                        <p style="font-size:12.5px;color:#6b7280;line-height:1.6;margin:0;">
                            For any queries regarding this invoice, please don't hesitate to contact us.
                        </p>
                        <p style="font-size:13px;color:#0f1115;font-weight:600;margin:14px 0 0;">
                            {{ $data['company_name'] }}
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#0f1115;padding:18px 32px;">
                        <div style="font-size:11px;color:#9ca3af;line-height:1.6;">
                            This is an automated email from {{ $data['company_name'] }}.<br>
                            Invoice sent on {{ $data['generated_on'] }} &nbsp;&bull;&nbsp; Please do not reply to this email.
                        </div>
                    </td>
                </tr>

            </table>

            {{-- Outer footer --}}
            <table role="presentation" width="600" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:16px 8px;text-align:center;">
                        <div style="font-size:11px;color:#9ca3af;">&copy; {{ date('Y') }} {{ $data['company_name'] }}. All rights reserved.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

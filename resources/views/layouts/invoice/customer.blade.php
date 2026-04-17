<?php
$companyDetails = \App\Models\CompanyDetailModel::info();
if( isset($customer_id) && !empty($customer_id) ){
	$customerDetails = \App\Models\Customer::info($customer_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
	<style>
	a,hr{color:inherit}progress,sub,sup{vertical-align:baseline}blockquote,body,dd,dl,fieldset,figure,h1,h2,h3,h4,h5,h6,hr,menu,ol,p,pre,ul{margin:0}dialog,fieldset,legend,menu,ol,ul{padding:0}.border-collapse,table{border-collapse:collapse}*,::after,::before{box-sizing:border-box;border:0 solid #e5e7eb;--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: }::after,::before{--tw-content:''}html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal}body{line-height:inherit}hr{height:0;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}[type=button],[type=reset],[type=submit],button{-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}menu,ol,ul{list-style:none}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]{display:none}::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: }.fixed{position:fixed}.bottom-0{bottom:0}.left-0{left:0}.table{display:table}.h-12{height:3rem}.w-1\/2{width:50%}.w-full{width:100%}.border-spacing-0{--tw-border-spacing-x:0px;--tw-border-spacing-y:0px;border-spacing:var(--tw-border-spacing-x) var(--tw-border-spacing-y)}.whitespace-nowrap{white-space:nowrap}.border-b{border-bottom-width:1px}.border-b-2{border-bottom-width:2px}.border-r{border-right-width:1px}.border-main{border-color:#5c6ac4}.bg-main{background-color:#5c6ac4}.bg-slate-100{background-color:#f1f5f9}.p-3{padding:.75rem}.px-14{padding-left:3.5rem;padding-right:3.5rem}.pl-2,.px-2{padding-left:.5rem}.px-2{padding-right:.5rem}.py-10{padding-top:2.5rem;padding-bottom:2.5rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}.py-4{padding-top:1rem;padding-bottom:1rem}.py-6{padding-top:1.5rem;padding-bottom:1.5rem}.pb-3{padding-bottom:.75rem}.pl-3{padding-left:.75rem}.pl-4{padding-left:1rem}.pr-3{padding-right:.75rem}.pr-4{padding-right:1rem}.text-center{text-align:center}.text-right{text-align:right}.align-top{vertical-align:top}.text-sm{font-size:.875rem;line-height:1.25rem}.text-xs{font-size:.75rem;line-height:1rem}.font-bold{font-weight:700}.italic{font-style:italic}.text-main{color:#5c6ac4}.text-neutral-600{color:#525252}.text-neutral-700{color:#404040}.text-slate-300{color:#cbd5e1}.text-slate-400{color:#94a3b8}.text-white{color:#fff}@page{margin:0}@media print{body{-webkit-print-color-adjust:exact}}
	.mt-20{
		margin-top:20px;
	}
	.mb-20{
		margin-bottom:20px;
	}
	.font-16{
		font-size:16px;font-weight:bold;
	}
	</style>
</head>

<body>
  <div>
    <div class="py-4">
      <div class="px-14 py-6">
        <table class="w-full border-collapse border-spacing-0" border=1>
          <tbody>
            <tr>
              <td class="w-full align-top">
                <div>
					@isset($companyDetails->invoice_logo)
					<img class="brand-logo h-12" style="" alt="stack admin logo" src="{{config('filesystems.disks.public.url')}}/{{$companyDetails->invoice_logo}}" weight="40px" height='80px'/>
					@else
					<img class="brand-logo h-12" style="" alt="stack admin logo" src="{{asset('loginDesign/img/dashboard-logo.png')}}" weight="40px" height='80px'/>
					@endisset
                </div>
              </td>

              <td class="align-top">
                <div class="text-sm">
                  <table class="border-collapse border-spacing-0">
                    <!--
					<tbody>
                      <tr>
                        <td class="border-r pr-4">
                          <div>
                            <p class="whitespace-nowrap text-slate-400 text-right">Date</p>
                            <p class="whitespace-nowrap font-bold text-main text-right">April 26, 2023</p>
                          </div>
                        </td>
                        <td class="pl-4">
                          <div>
                            <p class="whitespace-nowrap text-slate-400 text-right">Invoice #</p>
                            <p class="whitespace-nowrap font-bold text-main text-right">BRA-00335</p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
					-->
					@hasSection('top')
						@yield('top')
					@endif
                  </table>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-slate-100 px-14 py-6 text-sm">
        <table class="w-full border-collapse border-spacing-0">
          <tbody>
            <tr>
              <td class="w-1/2 align-top">
                <div class="text-sm text-neutral-600">
                  <p class="font-bold">{{$companyDetails->company_name ?? ''}}</p>
                  <p>Phone: {{$companyDetails->telephone ?? ''}}</p>
                  <p>VAT: 23456789</p>
                  <p>Email: {{$companyDetails->email ?? ''}}</p>
                  <p>{{$companyDetails->address1 ?? ''}}, {{$companyDetails->zipcode ?? ''}}</p>
                  <p>{{$companyDetails->country ?? ''}}</p>
                </div>
              </td>
			  @if(isset($customer_id) && ! empty($customer_id) )
              <td class="w-1/2 align-top text-right">
                <div class="text-sm text-neutral-600">
                  <p class="font-bold">{{$customerDetails->name}}</p>
                  <p>Phone: {{$customerDetails->mobile}}</p>
                  <p>Email: {{$customerDetails->email}}</p>
                  <p>{{$customerDetails->address1}}</p>
                  <p>{{$customerDetails->state}}, {{$customerDetails->city}}, {{$customerDetails->zipcode}}</p>
                  <p>{{$customerDetails->country}}</p>
                </div>
              </td>
			  @endif
            </tr>
          </tbody>
        </table>
      </div>
		@hasSection('content')
			@yield('content')
		@endif
      <div class="px-14 text-sm text-neutral-700 pt-0">
        <p class="text-main font-bold">Notes</p>
        <p class="italic">Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries
          for previewing layouts and visual mockups.</p>
        </div>
      </div>
    </div>
	@if(isset($print) && $print == 1)
	<script>
		window.onload = function() {
			window.print();
		};
	</script>
	@endif
</body>
</html>

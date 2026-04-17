<!DOCTYPE html>
<html>
<head>
    <title>API Runner</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        pre {
            background: #1d1f21;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            overflow: auto;
        }
        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            height: 37px;
        }
        #getLink {
            display: none;
            margin-top: 6px;
        }
    </style>
</head>

<body class="p-4">

<div class="container">
    <h3 class="mb-4">API Runner (AJAX Based)</h3>

    <div class="card p-3">

        <!-- Method + URL -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">HTTP Method</label>
                <select class="form-select" id="method">
                    <option>GET</option>
                    <option selected>POST</option>
                    <option>PUT</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                </select>
            </div>

            <div class="col-md-9">
                <label class="form-label">Route</label>

                <select class="form-select" id="urlSelect">
                    <option value="">-- Select Route --</option>

                    @foreach (\Route::getRoutes() as $route)
                        @php
                            $uri = $route->uri();
                            $method = $route->methods()[0] ?? 'GET';

                            if ($uri === 'sanctum/csrf-cookie') continue;
                        @endphp

                        <option value="/{{ $uri }}">
                            {{ $method }} - /{{ $uri }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- JSON Request -->
        <div class="mb-3">
            <label class="form-label">JSON Request Body</label>
            <textarea id="jsonInput" class="form-control" rows="4">{}</textarea>

            <!-- GET link -->
            <a href="#" target="_blank" id="getLink" class="text-primary">🔗 Open GET URL in new tab</a>
        </div>

        <button id="runApi" class="btn btn-primary">Run API</button>
    </div>

    <!-- Response -->
    <div class="card p-3 mt-4">
        <h5>Response:</h5>
        <pre id="responseBox">{}</pre>
    </div>
</div>

<script>

// Enable Select2
$("#urlSelect").select2({
    placeholder: "Search any route...",
    allowClear: true,
});

// Convert nested JSON → query string
function jsonToQueryString(data, prefix = "") {
    let query = [];

    for (let key in data) {
        if (!data.hasOwnProperty(key)) continue;

        let value = data[key];
        let fullKey = prefix ? `${prefix}[${key}]` : key;

        if (typeof value === "object" && value !== null) {
            query.push(jsonToQueryString(value, fullKey));
        } else {
            query.push(encodeURIComponent(fullKey) + "=" + encodeURIComponent(value));
        }
    }

    return query.join("&");
}

// Update GET link dynamically
function updateGetLink() {
    if ($("#method").val() !== "GET") {
        $("#getLink").hide();
        return;
    }

    let url = $("#urlSelect").val();
    if (!url) {
        $("#getLink").hide();
        return;
    }

    let rawJson = $("#jsonInput").val();
    let jsonData = {};

    // Validate JSON
    try {
        jsonData = JSON.parse(rawJson || "{}");
    } catch {
        $("#getLink").hide();
        return;
    }

    let qs = jsonToQueryString(jsonData);
    let fullUrl = window.location.origin + url + (qs ? "?" + qs : "");

    $("#getLink")
        .attr("href", fullUrl)
        .text("🔗 Open GET URL in new tab")
        .show();
}

// Apply link updates
$("#method").on("change", updateGetLink);
$("#jsonInput").on("input", updateGetLink);
$("#urlSelect").on("change", updateGetLink);

// Main AJAX runner
$("#runApi").click(function () {

    let method = $("#method").val();
    let url = $("#urlSelect").val();
    let rawJson = $("#jsonInput").val();

    if (!url) {
        $("#responseBox").text("❌ Please select a route");
        return;
    }

    let jsonData = {};

    try {
        jsonData = JSON.parse(rawJson || "{}");
    } catch (err) {
        $("#responseBox").text("❌ Invalid JSON");
        return;
    }

    // Append query string for GET
    if (method === "GET") {
        let qs = jsonToQueryString(jsonData);
        if (qs) url += "?" + qs;
    }

    $.ajax({
        url: url,
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
        },
        data: method === "GET" ? {} : JSON.stringify(jsonData),
        success: function (res) {
            $("#responseBox").text(JSON.stringify(res, null, 4));
        },
        error: function (xhr) {
            let err = {
                status: xhr.status,
                statusText: xhr.statusText,
                response: xhr.responseText
            };
            $("#responseBox").text(JSON.stringify(err, null, 4));
        }
    });

});
</script>

</body>
</html>

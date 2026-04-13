<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://127.0.0.1:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.6.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.6.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-properties">
                                <a href="#endpoints-GETapi-properties">GET api/properties</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-properties-random">
                                <a href="#endpoints-GETapi-properties-random">GET api/properties/random</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-properties--id-">
                                <a href="#endpoints-GETapi-properties--id-">GET api/properties/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties">
                                <a href="#endpoints-POSTapi-properties">POST api/properties</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-properties--id-">
                                <a href="#endpoints-PUTapi-properties--id-">PUT api/properties/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-properties--id-">
                                <a href="#endpoints-DELETEapi-properties--id-">DELETE api/properties/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--approve">
                                <a href="#endpoints-POSTapi-properties--id--approve">POST api/properties/{id}/approve</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--reject">
                                <a href="#endpoints-POSTapi-properties--id--reject">POST api/properties/{id}/reject</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--mark-sold">
                                <a href="#endpoints-POSTapi-properties--id--mark-sold">POST api/properties/{id}/mark-sold</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--archive">
                                <a href="#endpoints-POSTapi-properties--id--archive">POST api/properties/{id}/archive</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--restore">
                                <a href="#endpoints-POSTapi-properties--id--restore">POST api/properties/{id}/restore</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-properties--id--status">
                                <a href="#endpoints-PUTapi-properties--id--status">PUT api/properties/{id}/status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-properties-statistics">
                                <a href="#endpoints-GETapi-properties-statistics">GET api/properties/statistics</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-properties--id--toggle-favorite">
                                <a href="#endpoints-POSTapi-properties--id--toggle-favorite">POST api/properties/{id}/toggle-favorite</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories-public">
                                <a href="#endpoints-GETapi-categories-public">GET api/categories/public</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories-public--id-">
                                <a href="#endpoints-GETapi-categories-public--id-">GET api/categories/public/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories">
                                <a href="#endpoints-GETapi-categories">GET api/categories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-categories">
                                <a href="#endpoints-POSTapi-categories">POST api/categories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories--id-">
                                <a href="#endpoints-GETapi-categories--id-">GET api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-categories--id-">
                                <a href="#endpoints-PUTapi-categories--id-">PUT api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-categories--id-">
                                <a href="#endpoints-DELETEapi-categories--id-">DELETE api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-register">
                                <a href="#endpoints-POSTapi-auth-register">Create a new registered user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-login">
                                <a href="#endpoints-POSTapi-auth-login">Attempt to authenticate a new session.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-two-factor-challenge">
                                <a href="#endpoints-POSTapi-auth-two-factor-challenge">Attempt to authenticate a new session using the two factor authentication code.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-forgot-password">
                                <a href="#endpoints-POSTapi-auth-forgot-password">Send a reset link to the given user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-reset-password">
                                <a href="#endpoints-POSTapi-auth-reset-password">Reset the user's password.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-email-verify--id---hash-">
                                <a href="#endpoints-GETapi-auth-email-verify--id---hash-">Mark the authenticated user's email address as verified.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-logout">
                                <a href="#endpoints-POSTapi-auth-logout">Destroy an authenticated session.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-email-verification-notification">
                                <a href="#endpoints-POSTapi-auth-email-verification-notification">Send a new email verification notification.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-auth-user-profile-information">
                                <a href="#endpoints-PUTapi-auth-user-profile-information">Update the user's profile information.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-auth-user-password">
                                <a href="#endpoints-PUTapi-auth-user-password">Update the user's password.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-user-confirm-password">
                                <a href="#endpoints-POSTapi-auth-user-confirm-password">Confirm the user's password.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-user-two-factor-authentication">
                                <a href="#endpoints-POSTapi-auth-user-two-factor-authentication">Enable two factor authentication for the user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-auth-user-two-factor-authentication">
                                <a href="#endpoints-DELETEapi-auth-user-two-factor-authentication">Disable two factor authentication for the user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-user-two-factor-qr-code">
                                <a href="#endpoints-GETapi-auth-user-two-factor-qr-code">Get the SVG element for the user's two factor authentication QR code.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-user-two-factor-secret-key">
                                <a href="#endpoints-GETapi-auth-user-two-factor-secret-key">Get the current user's two factor authentication setup / secret key.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-user-two-factor-recovery-codes">
                                <a href="#endpoints-GETapi-auth-user-two-factor-recovery-codes">Get the two factor authentication recovery codes for authenticated user.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-user-two-factor-recovery-codes">
                                <a href="#endpoints-POSTapi-auth-user-two-factor-recovery-codes">Generate a fresh set of two factor authentication recovery codes.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-location-cities">
                                <a href="#endpoints-GETapi-location-cities">GET api/location/cities</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-location-cities">
                                <a href="#endpoints-POSTapi-location-cities">POST api/location/cities</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-location-cities--id-">
                                <a href="#endpoints-GETapi-location-cities--id-">GET api/location/cities/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-location-cities--id-">
                                <a href="#endpoints-PUTapi-location-cities--id-">PUT api/location/cities/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-location-cities--id-">
                                <a href="#endpoints-DELETEapi-location-cities--id-">DELETE api/location/cities/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-location-countries">
                                <a href="#endpoints-GETapi-location-countries">GET api/location/countries</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-location-countries">
                                <a href="#endpoints-POSTapi-location-countries">POST api/location/countries</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-location-countries--id-">
                                <a href="#endpoints-GETapi-location-countries--id-">GET api/location/countries/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-location-countries--id-">
                                <a href="#endpoints-PUTapi-location-countries--id-">PUT api/location/countries/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-location-countries--id-">
                                <a href="#endpoints-DELETEapi-location-countries--id-">DELETE api/location/countries/{id}</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: April 13, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://127.0.0.1:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-properties">GET api/properties</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-properties">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/properties" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-properties">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: null,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 11,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Modern Apartment in Calgary&quot;,
            &quot;description&quot;: &quot;Voluptate sit quas voluptatem eos. Non nulla ut voluptatem qui. Qui quae iusto porro harum quisquam delectus beatae quasi. Non repellendus sequi expedita similique inventore molestias. Aut enim hic sit deleniti.\n\nSunt ex mollitia aspernatur voluptates deserunt non recusandae. Voluptas non consequatur fugit vitae debitis sequi nemo. Eius aut et ab accusantium vel.\n\nNon quasi provident aut provident nihil ut natus maxime. Quia vero laborum ipsam qui autem deleniti iste non. Officia itaque et voluptas itaque vel aut accusantium. Voluptate expedita error est ut officia.&quot;,
            &quot;country_id&quot;: 3,
            &quot;city_id&quot;: 17,
            &quot;longitude&quot;: &quot;-144.34732400&quot;,
            &quot;latitude&quot;: &quot;-29.02671800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Canada&quot;,
                &quot;code&quot;: &quot;CAN&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 17,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Calgary&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;land&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 2,
            &quot;area&quot;: &quot;309.30&quot;,
            &quot;detailed_info&quot;: &quot;Quo ad vero consequatur nihil. Id eos expedita qui sed velit et. Quibusdam sapiente harum eligendi.\n\nNesciunt cumque ut occaecati veniam aspernatur voluptas a. Voluptatum ipsa deleniti ullam dolores. Porro et rerum id voluptatem reiciendis sint sit. Sit nulla ipsum aspernatur incidunt libero adipisci.\n\nIure laborum eveniet et incidunt voluptas magni expedita. Est unde a iure et. Quos voluptas aut et autem voluptas ex. Placeat nihil est exercitationem. Est quasi sed quis nostrum reprehenderit veritatis voluptate.\n\nIn in quia optio nemo doloribus eos. Non magni aperiam eos minima alias illum. Iusto quisquam et sequi quis ipsa.\n\nIllum eligendi sint quod quae. Dolorem quis unde optio. Voluptatem voluptatem cumque officia animi et amet fugit.&quot;,
            &quot;price&quot;: &quot;1125331.50&quot;,
            &quot;currency&quot;: &quot;CAD&quot;,
            &quot;formatted_price&quot;: &quot;CAD 1,125,331.50&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 20,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Birmingham&quot;,
            &quot;description&quot;: &quot;Voluptas cumque sunt qui perspiciatis. Distinctio sed necessitatibus dignissimos. Amet voluptates nulla odio veniam sint adipisci voluptatem. Omnis corporis ut consectetur recusandae illum ducimus et sapiente.\n\nVelit nesciunt at consequatur ut. Voluptatem commodi voluptas possimus nostrum exercitationem qui. Est earum praesentium dolore sit cumque optio quia sunt. Nulla odio rerum repellendus et et facere.\n\nSit rem quo pariatur. Ut nihil dignissimos dolor.&quot;,
            &quot;country_id&quot;: 2,
            &quot;city_id&quot;: 11,
            &quot;longitude&quot;: &quot;0.40157000&quot;,
            &quot;latitude&quot;: &quot;60.58932400&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United Kingdom&quot;,
                &quot;code&quot;: &quot;GBR&quot;,
                &quot;phone_code&quot;: &quot;+44&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 11,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Birmingham&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 7,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;77.48&quot;,
            &quot;detailed_info&quot;: &quot;In asperiores assumenda voluptatem quia blanditiis. Error id asperiores alias mollitia veritatis. Dignissimos aut ut similique veritatis placeat provident voluptatem.\n\nVoluptas commodi magnam qui repellendus sed. Alias id hic ea porro. Ipsa sit corrupti optio exercitationem.\n\nNam nihil eos exercitationem iste mollitia consequatur. Illo et sunt est velit cumque. Necessitatibus excepturi tenetur voluptates beatae quo excepturi fugit. Rerum quisquam sapiente animi accusantium eum.\n\nNihil repellendus unde et. Nihil quas libero sequi quia voluptatem quae. Cumque vitae laboriosam doloribus.\n\nOccaecati quae saepe quis eos esse cum. Qui officia consectetur a voluptas modi est. Autem doloribus corporis tempora quasi. Quas minus et facere minus impedit possimus. Ullam eos sint nesciunt sed ratione ut.&quot;,
            &quot;price&quot;: &quot;4046999.51&quot;,
            &quot;currency&quot;: &quot;AUD&quot;,
            &quot;formatted_price&quot;: &quot;AUD 4,046,999.51&quot;,
            &quot;status&quot;: &quot;sold&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-03 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 19,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Cozy Studio in Vancouver&quot;,
            &quot;description&quot;: &quot;Quo incidunt esse et aut. Et expedita impedit rerum dolor. Et quos iure sed aut sit.\n\nSed id et dolores et. Soluta et expedita eveniet aliquid et quis. Quasi ut autem fugit aut id. Architecto natus aspernatur est laudantium.\n\nOmnis ipsum excepturi minus omnis eum reprehenderit omnis sunt. Iste rerum est maiores blanditiis aut omnis. Temporibus quis rerum aut ea nesciunt dolor tenetur.&quot;,
            &quot;country_id&quot;: 3,
            &quot;city_id&quot;: 15,
            &quot;longitude&quot;: &quot;176.01889900&quot;,
            &quot;latitude&quot;: &quot;50.72217400&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Canada&quot;,
                &quot;code&quot;: &quot;CAN&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 15,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Vancouver&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 4,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;191.85&quot;,
            &quot;detailed_info&quot;: &quot;Voluptas natus praesentium error earum debitis ut voluptas libero. Eius eligendi aut ipsa et exercitationem ut vitae.\n\nAliquam reprehenderit voluptatem aspernatur qui totam. Voluptates error nihil placeat impedit. Voluptatum quasi sed necessitatibus consectetur magni. Pariatur adipisci tenetur non nulla.\n\nPerspiciatis nisi voluptatem eum accusantium aperiam saepe. Rerum velit dolorem esse omnis odit. Laudantium molestiae vero qui.\n\nEt eveniet optio id rerum omnis natus. Impedit quo repudiandae ipsa ad neque et voluptas libero. Animi doloribus quidem accusantium recusandae quia voluptatem corporis.\n\nAdipisci est cupiditate odio molestias. Impedit commodi a quia laboriosam porro in. Voluptates illo perspiciatis rerum optio voluptatem tempora quod. Nemo dolores fuga ut vel nobis vel aut est.&quot;,
            &quot;price&quot;: &quot;3227799.53&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 3,227,799.53&quot;,
            &quot;status&quot;: &quot;sold&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-03-14 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 18,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Penthouse Suite in Montreal&quot;,
            &quot;description&quot;: &quot;Nostrum et debitis temporibus velit qui aut repudiandae. Quam ab praesentium voluptatum sunt nesciunt et voluptatum. Qui autem eum odit modi commodi.\n\nFuga iure alias vitae. Quas voluptate aut provident qui. Vel reprehenderit tempore totam reiciendis excepturi aperiam rerum.\n\nVoluptatem quam nisi omnis aut eos vitae. Voluptatem blanditiis quia saepe autem. Occaecati voluptates recusandae quas officiis aperiam. Rerum tenetur aut aliquid.&quot;,
            &quot;country_id&quot;: 3,
            &quot;city_id&quot;: 16,
            &quot;longitude&quot;: &quot;-37.19481700&quot;,
            &quot;latitude&quot;: &quot;4.68212600&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Canada&quot;,
                &quot;code&quot;: &quot;CAN&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 16,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Montreal&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;commercial&quot;,
            &quot;rooms&quot;: 4,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;482.81&quot;,
            &quot;detailed_info&quot;: &quot;Iusto enim omnis nostrum velit et. Et nemo illo qui animi quas iste illum blanditiis. Dolores excepturi reprehenderit fuga perspiciatis cumque unde.\n\nEnim numquam culpa repellat non voluptate itaque. Eligendi voluptate earum sed modi neque voluptates asperiores. Ab quaerat quis accusantium consequuntur at. Quia sed delectus nobis tempore.\n\nEst molestias modi rerum veritatis commodi. Minima blanditiis odit occaecati doloribus enim et facere amet. Omnis maiores nobis quidem ut animi suscipit quia consequatur. Consequatur id harum aut omnis ullam.\n\nVoluptatem sapiente eius inventore facilis ea dolor velit. Ex est vitae ratione et voluptate rerum aut. Id voluptatem tempora odit dolores molestias beatae. Nobis aspernatur laboriosam rerum similique error hic. Magnam commodi magni et non.\n\nVoluptatem nostrum asperiores et. Tenetur perferendis rem consequatur omnis placeat sapiente sunt cumque. In et excepturi nostrum voluptas.&quot;,
            &quot;price&quot;: &quot;3847707.75&quot;,
            &quot;currency&quot;: &quot;EUR&quot;,
            &quot;formatted_price&quot;: &quot;EUR 3,847,707.75&quot;,
            &quot;status&quot;: &quot;sold&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-03-19 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 17,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Cozy Studio in Perth&quot;,
            &quot;description&quot;: &quot;Sit ut qui ipsam. Sunt error natus recusandae. Deserunt eligendi est saepe quia blanditiis quisquam saepe. Qui est et repudiandae vel.\n\nQuia laborum repellendus voluptatum et. Sequi iure porro explicabo assumenda ipsam. Voluptatibus ipsa libero impedit ut distinctio iste est.\n\nNatus laudantium delectus impedit deleniti velit. Et maxime enim consectetur odit. Voluptatem aut itaque unde maiores dignissimos.&quot;,
            &quot;country_id&quot;: 4,
            &quot;city_id&quot;: 21,
            &quot;longitude&quot;: &quot;167.36725200&quot;,
            &quot;latitude&quot;: &quot;45.39158700&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 4,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Australia&quot;,
                &quot;code&quot;: &quot;AUS&quot;,
                &quot;phone_code&quot;: &quot;+61&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 21,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Perth&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;house&quot;,
            &quot;rooms&quot;: 5,
            &quot;bathrooms&quot;: 4,
            &quot;area&quot;: &quot;361.60&quot;,
            &quot;detailed_info&quot;: &quot;Quae beatae molestiae omnis consequuntur cupiditate aut. Deserunt laboriosam cum sed dolor est facilis laboriosam iste. Nulla consectetur cupiditate autem.\n\nPlaceat et ut aliquam quidem reprehenderit voluptas. Ducimus veritatis dolores provident excepturi sit quod et. Exercitationem non qui tenetur. Velit vitae consequuntur et voluptatem.\n\nDelectus rerum porro et. Sint vel nulla quia labore debitis. Rerum magni sequi in laboriosam numquam quis veniam. Numquam laborum sapiente blanditiis nam laudantium a.\n\nQuis harum impedit sapiente magnam molestiae. Unde voluptatem ut et rem maxime natus. Suscipit voluptate eaque distinctio ea veniam quis aliquid.\n\nRerum culpa amet pariatur nostrum nulla non asperiores. Consectetur nam dolorem molestiae dolor quaerat. Consequatur nemo numquam quasi consequatur. Tempora quia quibusdam eligendi.&quot;,
            &quot;price&quot;: &quot;1902320.79&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 1,902,320.79&quot;,
            &quot;status&quot;: &quot;rejected&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 16,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Beach House in Frankfurt&quot;,
            &quot;description&quot;: &quot;Voluptas et cumque tenetur aperiam repellendus. Rem porro et exercitationem iusto sint. Iste ipsa aut voluptas laborum harum laborum voluptatum.\n\nSit vitae inventore inventore ipsum et totam quod. Est aut quia sit quia provident reiciendis eos. Sint qui velit ipsam qui nam rerum esse. Omnis iste dolore id est repudiandae.\n\nId tenetur rerum aspernatur sed eligendi et rerum. Magni aliquam sit beatae non explicabo ab illo. At nemo vel aut eos.&quot;,
            &quot;country_id&quot;: 5,
            &quot;city_id&quot;: 24,
            &quot;longitude&quot;: &quot;-48.53152500&quot;,
            &quot;latitude&quot;: &quot;-22.36085900&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 5,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Germany&quot;,
                &quot;code&quot;: &quot;DEU&quot;,
                &quot;phone_code&quot;: &quot;+49&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 24,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Frankfurt&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;warehouse&quot;,
            &quot;rooms&quot;: 9,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;456.26&quot;,
            &quot;detailed_info&quot;: &quot;Quasi omnis et dolore dolorem. Assumenda illo harum modi. Reprehenderit veritatis incidunt itaque facere. Vero et veniam autem ratione.\n\nRem ea reprehenderit voluptas. Velit molestias incidunt distinctio est. Accusamus praesentium ut natus aut qui velit.\n\nSuscipit voluptas dolorem est laborum rerum. Maiores sunt est assumenda temporibus modi sit et. Commodi atque exercitationem natus suscipit nihil. Aut beatae et quia harum velit explicabo eveniet. Voluptatem sit voluptatem inventore repudiandae eaque labore et.\n\nArchitecto nulla reprehenderit eius voluptates at officiis. Velit quia recusandae at libero. Beatae eius sequi libero. Ad minima similique quos consequuntur sed.\n\nSit veniam quo dolor. Commodi nam non expedita et cupiditate. Qui eos dolore cumque praesentium est natus. Enim sed laborum similique cumque culpa quo vero quo.&quot;,
            &quot;price&quot;: &quot;4613429.93&quot;,
            &quot;currency&quot;: &quot;CAD&quot;,
            &quot;formatted_price&quot;: &quot;CAD 4,613,429.93&quot;,
            &quot;status&quot;: &quot;rejected&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 15,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Country Estate in Lyon&quot;,
            &quot;description&quot;: &quot;Error nam enim tempora in explicabo. Ipsa dignissimos nesciunt tempore eum aut. Sed laudantium libero error adipisci natus.\n\nMolestias repellendus quae ipsam sint. Earum exercitationem quasi ut quia vitae ut. Omnis et commodi quidem nemo quibusdam error veritatis harum. Quo praesentium rem et debitis officia.\n\nOmnis quis dolor facilis aliquam ducimus inventore placeat error. Non et sed dolorem et. Sed voluptas libero quas et quae minima. Odit tempore maxime et quo qui sit.&quot;,
            &quot;country_id&quot;: 6,
            &quot;city_id&quot;: 27,
            &quot;longitude&quot;: &quot;33.04282400&quot;,
            &quot;latitude&quot;: &quot;-41.18907200&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 6,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;France&quot;,
                &quot;code&quot;: &quot;FRA&quot;,
                &quot;phone_code&quot;: &quot;+33&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 27,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Lyon&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;warehouse&quot;,
            &quot;rooms&quot;: 10,
            &quot;bathrooms&quot;: 4,
            &quot;area&quot;: &quot;40.17&quot;,
            &quot;detailed_info&quot;: &quot;Dolores fuga et nemo ullam eveniet est omnis. Consequatur sint autem cum modi earum inventore commodi. Qui corrupti sint tempore et consequatur consectetur adipisci quae. Quia impedit laborum quos illo cupiditate sit commodi.\n\nAt aut voluptatibus ab perspiciatis reiciendis asperiores. Est iste in sint magni voluptatem. Animi in accusamus vel voluptatibus quia modi voluptates. Doloremque sit et laudantium inventore.\n\nQuisquam pariatur ipsum alias. Commodi omnis architecto explicabo aut quo voluptas. Pariatur facere cupiditate at dignissimos sint reprehenderit. Aperiam voluptatem expedita qui iusto veniam porro.\n\nTotam quia ullam rerum vero consequatur quod explicabo. Sunt consectetur veritatis optio nisi. Quos nihil quas ratione itaque deserunt ut. Et debitis cum velit in.\n\nQuam voluptatem aut laborum eum explicabo. Distinctio doloribus repudiandae placeat minus quisquam debitis. Aut deleniti consequatur distinctio voluptas accusamus occaecati facilis. Consequatur culpa quis ipsam saepe dolor hic sint.&quot;,
            &quot;price&quot;: &quot;3083025.06&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 3,083,025.06&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 14,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Mountain Retreat in Chicago&quot;,
            &quot;description&quot;: &quot;Quis et nostrum non blanditiis. Ut modi quibusdam aspernatur sunt. A ipsam quidem tempore. Quia debitis amet sit eos perspiciatis.\n\nAd nihil et et non tempore. Accusantium est amet vero quis. Deleniti ipsam qui atque sed et. Dolorem modi quia consequatur nulla porro.\n\nEx quis animi dolor et eaque quas. Unde rerum rem aut quaerat voluptatum. Et in iste vero non necessitatibus neque sit.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;83.93032800&quot;,
            &quot;latitude&quot;: &quot;26.82477700&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 9,
            &quot;bathrooms&quot;: 4,
            &quot;area&quot;: &quot;247.15&quot;,
            &quot;detailed_info&quot;: &quot;Hic et nemo minima et non qui omnis. Id sit explicabo sunt minima earum dolorem. Et consequatur tempore maiores. Consequatur incidunt temporibus aliquid iure doloribus. Natus architecto cupiditate quidem asperiores rerum.\n\nEst velit necessitatibus quo iure facere accusamus. Ipsa similique labore nemo quia laborum similique quibusdam. Magnam ea eos aut ut error. In libero suscipit consequuntur nulla est dolores.\n\nArchitecto facilis earum fugiat officiis. Voluptatem voluptatum sint sint adipisci et. Autem voluptatibus iusto natus et soluta a. Sed eaque voluptas et quo dignissimos aut sed est.\n\nEt est et officiis consequatur quia odit totam. Ullam doloribus pariatur rem dolores. Aut molestiae itaque et officiis et molestias ut.\n\nDolore eaque voluptates debitis corporis iure. Pariatur quas magni nostrum numquam. At optio qui aperiam numquam. Tempore consectetur est natus debitis sed.&quot;,
            &quot;price&quot;: &quot;4877447.59&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 4,877,447.59&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 13,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Chicago&quot;,
            &quot;description&quot;: &quot;Cum aut consequatur minima sapiente qui maiores molestiae. Quia consequatur illum nisi. Quis saepe quae ut iusto.\n\nVoluptatem sed iste mollitia facere error. Eligendi sit qui ab repudiandae fuga quae reprehenderit. Doloremque voluptas corrupti ex odit distinctio. Et ipsam corrupti quo nihil et. Maiores quam nulla non molestiae dicta.\n\nNesciunt fuga et aut omnis. Quisquam quam culpa repellat ab fugit perspiciatis. Enim explicabo et at consequatur sunt commodi. Sunt veniam fugiat non neque.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;36.02937900&quot;,
            &quot;latitude&quot;: &quot;2.45447800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;commercial&quot;,
            &quot;rooms&quot;: 4,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;150.30&quot;,
            &quot;detailed_info&quot;: &quot;Dolor corrupti suscipit blanditiis. Consequatur reprehenderit qui officiis ullam ex. Eos ut dolorem praesentium qui aspernatur saepe et. Impedit odio harum vero ducimus.\n\nPraesentium voluptas ea veritatis non. Officiis ut quas perspiciatis aut et minima fugit id. Consectetur nobis veniam incidunt impedit consequatur. Inventore commodi non repellendus laborum deserunt occaecati.\n\nOdio unde in ut earum ut. Aut et officia esse ut maiores dignissimos. Sit magni ipsa incidunt.\n\nVoluptas consequuntur suscipit cum est aut velit aut quia. Sit voluptatem ad rerum omnis et. Sint illo ut voluptas rerum aut soluta voluptate.\n\nRepellat error at quia numquam aut facilis molestias. Consequatur nulla dolor sequi autem nobis non. Eaque a iste dolores ut voluptatem.&quot;,
            &quot;price&quot;: &quot;1593610.75&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 1,593,610.75&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 12,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Country Estate in Chicago&quot;,
            &quot;description&quot;: &quot;Facilis qui esse voluptates distinctio voluptas neque nemo provident. Molestiae pariatur accusamus excepturi. Beatae quis eos nemo tenetur quo. Necessitatibus aspernatur aspernatur dolorum ab repellendus quo quo.\n\nOfficia voluptas molestias et autem nostrum. Totam dolorem nemo aperiam doloribus. Minus quia ea magni voluptas laborum quis nam.\n\nDelectus quis provident dolorum nesciunt quibusdam. Fugit quia architecto dicta sapiente quisquam. Ipsam voluptas molestiae eaque eveniet iure odit.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;146.21953900&quot;,
            &quot;latitude&quot;: &quot;89.14185600&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;villa&quot;,
            &quot;rooms&quot;: 8,
            &quot;bathrooms&quot;: 2,
            &quot;area&quot;: &quot;302.24&quot;,
            &quot;detailed_info&quot;: &quot;Maiores laboriosam incidunt fuga ex consequuntur. Quis non voluptas nobis quod. Exercitationem in vitae voluptatem qui mollitia tenetur.\n\nEx quae quia voluptatem et. Voluptatem est dicta aut. Itaque dolorem odio facere autem est.\n\nNulla qui rerum error aut dolorem omnis ut. Aspernatur in sed quis rerum quis eius illum. Temporibus sint ratione quia suscipit alias modi et. Cum velit tenetur dolor veritatis. Et incidunt deleniti commodi.\n\nCommodi debitis ut est autem rerum et. Earum voluptates culpa ut dolor at. Commodi assumenda alias autem aspernatur. Corporis cumque possimus veniam sit aut dignissimos veniam possimus. Dolorem in est id magni quos voluptas laudantium.\n\nLaudantium doloremque quasi facilis ut suscipit. Et nesciunt et sunt dolor hic. Debitis unde earum eaque et maiores autem. Molestias et quis officiis eveniet molestiae.&quot;,
            &quot;price&quot;: &quot;4749314.97&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 4,749,314.97&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 1,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Penthouse Suite in Brisbane&quot;,
            &quot;description&quot;: &quot;Alias quam quae voluptas autem earum totam cum suscipit. Optio accusamus eos perferendis mollitia minima dolore. Amet itaque dicta perspiciatis qui magnam molestias et itaque. Qui et rerum autem nihil minus.\n\nVeritatis commodi qui quo cumque. Harum a ullam ab et doloremque odio dolor. Laborum nulla voluptatibus ea illum. Voluptatem ea vel nulla quia error non.\n\nAdipisci nostrum sit sunt id commodi natus a quis. Magni impedit voluptatem adipisci minus. Ratione temporibus aut qui ipsa iste et voluptatem.&quot;,
            &quot;country_id&quot;: 4,
            &quot;city_id&quot;: 20,
            &quot;longitude&quot;: &quot;118.16232600&quot;,
            &quot;latitude&quot;: &quot;79.80186000&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 4,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Australia&quot;,
                &quot;code&quot;: &quot;AUS&quot;,
                &quot;phone_code&quot;: &quot;+61&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 20,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Brisbane&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;warehouse&quot;,
            &quot;rooms&quot;: 9,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;442.48&quot;,
            &quot;detailed_info&quot;: &quot;Sint culpa quam ex nulla. Molestias illo sit optio fuga laboriosam odit consequatur nihil. Ullam tempora repellat dolores libero pariatur delectus hic. Consequatur dolore cumque voluptatem vel cum aut. Quaerat aut iste sed ut odit iusto minima molestiae.\n\nVoluptas neque rem est sit. Libero iusto officia voluptatem et. Nihil vero numquam perferendis.\n\nConsequatur quas et eius ut. Aut fugiat et fuga sit ut atque accusantium. Voluptatem magni placeat illo incidunt minus tempore.\n\nNemo omnis rerum totam. Laudantium incidunt ipsa at vel ut numquam pariatur enim.\n\nDistinctio non consequatur at adipisci voluptatum non reprehenderit. Commodi minus impedit illum sit similique voluptas. Laborum velit alias cupiditate veniam. Sed qui repudiandae vero.&quot;,
            &quot;price&quot;: &quot;1274400.28&quot;,
            &quot;currency&quot;: &quot;CAD&quot;,
            &quot;formatted_price&quot;: &quot;CAD 1,274,400.28&quot;,
            &quot;status&quot;: &quot;pending&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: null,
            &quot;approved_at&quot;: null,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: null,
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 10,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Country Estate in Birmingham&quot;,
            &quot;description&quot;: &quot;Et similique alias enim eaque accusamus repellat praesentium. Est ut ipsa delectus id neque aliquid. Et dicta nesciunt optio. Fugit eius exercitationem libero debitis voluptatem.\n\nAut est velit architecto minima sit. Enim voluptatum non quia. Praesentium assumenda praesentium quisquam voluptates eum quisquam enim. Autem rem eum aut perspiciatis est sint atque. Reprehenderit doloribus vel praesentium.\n\nDelectus consequatur consequatur fuga enim nemo aut eveniet. Tempora earum hic cum expedita non ut unde. Numquam aut vero voluptatem natus repellat.&quot;,
            &quot;country_id&quot;: 2,
            &quot;city_id&quot;: 11,
            &quot;longitude&quot;: &quot;66.18438300&quot;,
            &quot;latitude&quot;: &quot;-24.98462200&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United Kingdom&quot;,
                &quot;code&quot;: &quot;GBR&quot;,
                &quot;phone_code&quot;: &quot;+44&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 11,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Birmingham&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;villa&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 3,
            &quot;area&quot;: &quot;320.86&quot;,
            &quot;detailed_info&quot;: &quot;Aliquid omnis vel accusamus repellat. Et quaerat mollitia ea. Et rerum est et ad et velit sapiente ab.\n\nFugit earum quaerat repudiandae ut explicabo aut. Ut nihil nam harum et. Non eius voluptatem hic et quae.\n\nEt necessitatibus itaque nemo ducimus aut eos. Maxime doloremque debitis qui dolores. Dolor ipsa atque sunt explicabo debitis eum impedit.\n\nEt corrupti et dicta tempora ut. Dolores iure sit alias et. Quo enim voluptates ut quia facere amet.\n\nHarum voluptate autem eligendi quae culpa asperiores vero omnis. Molestiae qui voluptatum voluptas sed deserunt sed. Nostrum quod fugiat et dolorem mollitia harum dignissimos.&quot;,
            &quot;price&quot;: &quot;3885719.88&quot;,
            &quot;currency&quot;: &quot;EUR&quot;,
            &quot;formatted_price&quot;: &quot;EUR 3,885,719.88&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 9,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Mountain Retreat in Chicago&quot;,
            &quot;description&quot;: &quot;Doloremque quam similique occaecati rerum. Consequatur ut et et nihil pariatur hic.\n\nNemo debitis veniam quia omnis distinctio debitis. Porro ipsum iste at quia eius dolor illum. Qui et quibusdam quo saepe fugit natus aut quos. Libero aut inventore veniam sed voluptatibus laborum a.\n\nId quia tempore enim magni deleniti perferendis doloribus. Omnis dolores distinctio quo et optio repellat atque suscipit. Ut sed corporis cumque qui ut velit placeat.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;151.18271500&quot;,
            &quot;latitude&quot;: &quot;42.15262900&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;310.63&quot;,
            &quot;detailed_info&quot;: &quot;Voluptatem distinctio earum nihil minima occaecati nesciunt atque. Assumenda laudantium magni vitae quia blanditiis ad. Quia omnis repellendus aspernatur sint.\n\nQui architecto voluptas minus facilis. Et ducimus ratione beatae et eos accusamus. Omnis et est dolores tempore voluptatibus aut.\n\nAccusamus et tempore ea voluptas laborum voluptate ut. Numquam unde quaerat doloribus sunt ex ex magni. Maxime et omnis asperiores ipsum. Et culpa suscipit vel eveniet et quas.\n\nRerum temporibus ullam rem incidunt vel neque officiis. Eius repellendus enim aut expedita et. Autem ipsa porro quo sed minima voluptatem eos.\n\nDolorem ea fuga nesciunt aut necessitatibus. Nesciunt eligendi illo dolor facere quos et. Et vero animi neque. Autem optio dolor nobis est natus possimus reiciendis.&quot;,
            &quot;price&quot;: &quot;4967953.50&quot;,
            &quot;currency&quot;: &quot;AUD&quot;,
            &quot;formatted_price&quot;: &quot;AUD 4,967,953.50&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 8,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Chicago&quot;,
            &quot;description&quot;: &quot;Suscipit nemo corporis explicabo magni. Dignissimos sunt distinctio tempora rerum sequi. Voluptate impedit porro vero maiores reiciendis natus. Natus ducimus repellendus est consequuntur quo.\n\nEa dolor labore illum doloremque. Magnam et aut dolores provident. Sunt et minima temporibus qui facere unde.\n\nVoluptatibus non culpa facere dolores est. Molestiae nihil non et officia. Aliquid ratione eaque velit ab sunt. Fugit expedita ducimus id.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;-125.60530200&quot;,
            &quot;latitude&quot;: &quot;28.38886800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;apartment&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 4,
            &quot;area&quot;: &quot;73.24&quot;,
            &quot;detailed_info&quot;: &quot;Et est illo ab facilis culpa. Nulla in quis ut dolor. Nam ut quis excepturi vel illum. Voluptatem ut dicta illum et.\n\nEt optio rerum laborum eos. Est est quo voluptate vel quae et et. Sunt velit explicabo accusamus tempora id quaerat voluptatum. Commodi id fugiat sed eveniet est accusamus saepe.\n\nDolorem velit accusantium amet suscipit qui. Ea aut sit neque nihil temporibus voluptatem. Qui temporibus magnam quis. Vitae et quas impedit neque.\n\nQuis beatae aspernatur tempore aut natus aliquam dolor. Consequuntur eum est quasi totam voluptatem magnam. Nostrum minus rem quia dolorem sit.\n\nRepellendus nesciunt doloribus perferendis est. Consequatur suscipit dolor omnis aut.&quot;,
            &quot;price&quot;: &quot;2743120.21&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 2,743,120.21&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 7,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Penthouse Suite in Manchester&quot;,
            &quot;description&quot;: &quot;Officiis hic aut dolores qui molestiae non modi. Omnis aliquid ea perferendis rerum qui officiis beatae et. Vel optio nihil aspernatur blanditiis ipsa debitis dolorum porro. Odit deserunt aperiam corporis accusantium placeat.\n\nDolorem fugit sunt autem doloremque minima. Accusamus est sapiente molestias. Tempore repellat vero consequatur vero provident praesentium est.\n\nMaxime eos veritatis sed dolores et. Blanditiis labore consequatur atque tempore voluptatem occaecati laudantium non. Veniam ad aut impedit aut repellendus nam fugiat. Qui in quis voluptatem sunt et eum. Quam blanditiis a labore nisi modi et veniam.&quot;,
            &quot;country_id&quot;: 2,
            &quot;city_id&quot;: 10,
            &quot;longitude&quot;: &quot;-48.65015200&quot;,
            &quot;latitude&quot;: &quot;-24.57322600&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United Kingdom&quot;,
                &quot;code&quot;: &quot;GBR&quot;,
                &quot;phone_code&quot;: &quot;+44&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 10,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Manchester&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;land&quot;,
            &quot;rooms&quot;: 9,
            &quot;bathrooms&quot;: 3,
            &quot;area&quot;: &quot;476.21&quot;,
            &quot;detailed_info&quot;: &quot;Temporibus ipsa beatae dolores. Ex dolorem impedit laborum eum ullam ad nihil. Aspernatur dolores velit sapiente ut cupiditate a vero.\n\nQuia est vitae fugiat voluptatum voluptates. Asperiores quas iste asperiores at sint nam aut quos. Non modi omnis et inventore ipsam sed praesentium.\n\nEligendi et necessitatibus repudiandae et libero sapiente in. Eaque consequatur amet ex laborum voluptatem nostrum maiores. Et accusamus architecto nesciunt voluptatem ex soluta. Est vero sed maxime assumenda et aut consequatur.\n\nDoloribus animi ut ducimus eos neque autem sit velit. Ea mollitia voluptas occaecati excepturi. Optio alias architecto voluptas facilis ad.\n\nError tempore optio molestiae et veniam temporibus animi. Veniam voluptas voluptas rerum quisquam sapiente consequatur numquam. Porro qui dignissimos aperiam quia accusantium. Est eveniet et aspernatur est et tenetur.&quot;,
            &quot;price&quot;: &quot;51720.38&quot;,
            &quot;currency&quot;: &quot;EUR&quot;,
            &quot;formatted_price&quot;: &quot;EUR 51,720.38&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        }
    ],
    &quot;pagination&quot;: {
        &quot;total&quot;: 20,
        &quot;per_page&quot;: 15,
        &quot;current_page&quot;: 1,
        &quot;last_page&quot;: 2,
        &quot;from&quot;: 1,
        &quot;to&quot;: 15
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-properties" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-properties"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-properties"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-properties" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-properties">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-properties" data-method="GET"
      data-path="api/properties"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-properties', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-properties"
                    onclick="tryItOut('GETapi-properties');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-properties"
                    onclick="cancelTryOut('GETapi-properties');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-properties"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/properties</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-properties"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-properties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-properties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-properties-random">GET api/properties/random</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-properties-random">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/properties/random" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/random"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-properties-random">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: null,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 8,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Chicago&quot;,
            &quot;description&quot;: &quot;Suscipit nemo corporis explicabo magni. Dignissimos sunt distinctio tempora rerum sequi. Voluptate impedit porro vero maiores reiciendis natus. Natus ducimus repellendus est consequuntur quo.\n\nEa dolor labore illum doloremque. Magnam et aut dolores provident. Sunt et minima temporibus qui facere unde.\n\nVoluptatibus non culpa facere dolores est. Molestiae nihil non et officia. Aliquid ratione eaque velit ab sunt. Fugit expedita ducimus id.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;-125.60530200&quot;,
            &quot;latitude&quot;: &quot;28.38886800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;apartment&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 4,
            &quot;area&quot;: &quot;73.24&quot;,
            &quot;detailed_info&quot;: &quot;Et est illo ab facilis culpa. Nulla in quis ut dolor. Nam ut quis excepturi vel illum. Voluptatem ut dicta illum et.\n\nEt optio rerum laborum eos. Est est quo voluptate vel quae et et. Sunt velit explicabo accusamus tempora id quaerat voluptatum. Commodi id fugiat sed eveniet est accusamus saepe.\n\nDolorem velit accusantium amet suscipit qui. Ea aut sit neque nihil temporibus voluptatem. Qui temporibus magnam quis. Vitae et quas impedit neque.\n\nQuis beatae aspernatur tempore aut natus aliquam dolor. Consequuntur eum est quasi totam voluptatem magnam. Nostrum minus rem quia dolorem sit.\n\nRepellendus nesciunt doloribus perferendis est. Consequatur suscipit dolor omnis aut.&quot;,
            &quot;price&quot;: &quot;2743120.21&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 2,743,120.21&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 9,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Mountain Retreat in Chicago&quot;,
            &quot;description&quot;: &quot;Doloremque quam similique occaecati rerum. Consequatur ut et et nihil pariatur hic.\n\nNemo debitis veniam quia omnis distinctio debitis. Porro ipsum iste at quia eius dolor illum. Qui et quibusdam quo saepe fugit natus aut quos. Libero aut inventore veniam sed voluptatibus laborum a.\n\nId quia tempore enim magni deleniti perferendis doloribus. Omnis dolores distinctio quo et optio repellat atque suscipit. Ut sed corporis cumque qui ut velit placeat.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;151.18271500&quot;,
            &quot;latitude&quot;: &quot;42.15262900&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;310.63&quot;,
            &quot;detailed_info&quot;: &quot;Voluptatem distinctio earum nihil minima occaecati nesciunt atque. Assumenda laudantium magni vitae quia blanditiis ad. Quia omnis repellendus aspernatur sint.\n\nQui architecto voluptas minus facilis. Et ducimus ratione beatae et eos accusamus. Omnis et est dolores tempore voluptatibus aut.\n\nAccusamus et tempore ea voluptas laborum voluptate ut. Numquam unde quaerat doloribus sunt ex ex magni. Maxime et omnis asperiores ipsum. Et culpa suscipit vel eveniet et quas.\n\nRerum temporibus ullam rem incidunt vel neque officiis. Eius repellendus enim aut expedita et. Autem ipsa porro quo sed minima voluptatem eos.\n\nDolorem ea fuga nesciunt aut necessitatibus. Nesciunt eligendi illo dolor facere quos et. Et vero animi neque. Autem optio dolor nobis est natus possimus reiciendis.&quot;,
            &quot;price&quot;: &quot;4967953.50&quot;,
            &quot;currency&quot;: &quot;AUD&quot;,
            &quot;formatted_price&quot;: &quot;AUD 4,967,953.50&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 12,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Country Estate in Chicago&quot;,
            &quot;description&quot;: &quot;Facilis qui esse voluptates distinctio voluptas neque nemo provident. Molestiae pariatur accusamus excepturi. Beatae quis eos nemo tenetur quo. Necessitatibus aspernatur aspernatur dolorum ab repellendus quo quo.\n\nOfficia voluptas molestias et autem nostrum. Totam dolorem nemo aperiam doloribus. Minus quia ea magni voluptas laborum quis nam.\n\nDelectus quis provident dolorum nesciunt quibusdam. Fugit quia architecto dicta sapiente quisquam. Ipsam voluptas molestiae eaque eveniet iure odit.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;146.21953900&quot;,
            &quot;latitude&quot;: &quot;89.14185600&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;villa&quot;,
            &quot;rooms&quot;: 8,
            &quot;bathrooms&quot;: 2,
            &quot;area&quot;: &quot;302.24&quot;,
            &quot;detailed_info&quot;: &quot;Maiores laboriosam incidunt fuga ex consequuntur. Quis non voluptas nobis quod. Exercitationem in vitae voluptatem qui mollitia tenetur.\n\nEx quae quia voluptatem et. Voluptatem est dicta aut. Itaque dolorem odio facere autem est.\n\nNulla qui rerum error aut dolorem omnis ut. Aspernatur in sed quis rerum quis eius illum. Temporibus sint ratione quia suscipit alias modi et. Cum velit tenetur dolor veritatis. Et incidunt deleniti commodi.\n\nCommodi debitis ut est autem rerum et. Earum voluptates culpa ut dolor at. Commodi assumenda alias autem aspernatur. Corporis cumque possimus veniam sit aut dignissimos veniam possimus. Dolorem in est id magni quos voluptas laudantium.\n\nLaudantium doloremque quasi facilis ut suscipit. Et nesciunt et sunt dolor hic. Debitis unde earum eaque et maiores autem. Molestias et quis officiis eveniet molestiae.&quot;,
            &quot;price&quot;: &quot;4749314.97&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 4,749,314.97&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 7,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Penthouse Suite in Manchester&quot;,
            &quot;description&quot;: &quot;Officiis hic aut dolores qui molestiae non modi. Omnis aliquid ea perferendis rerum qui officiis beatae et. Vel optio nihil aspernatur blanditiis ipsa debitis dolorum porro. Odit deserunt aperiam corporis accusantium placeat.\n\nDolorem fugit sunt autem doloremque minima. Accusamus est sapiente molestias. Tempore repellat vero consequatur vero provident praesentium est.\n\nMaxime eos veritatis sed dolores et. Blanditiis labore consequatur atque tempore voluptatem occaecati laudantium non. Veniam ad aut impedit aut repellendus nam fugiat. Qui in quis voluptatem sunt et eum. Quam blanditiis a labore nisi modi et veniam.&quot;,
            &quot;country_id&quot;: 2,
            &quot;city_id&quot;: 10,
            &quot;longitude&quot;: &quot;-48.65015200&quot;,
            &quot;latitude&quot;: &quot;-24.57322600&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United Kingdom&quot;,
                &quot;code&quot;: &quot;GBR&quot;,
                &quot;phone_code&quot;: &quot;+44&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 10,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Manchester&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;land&quot;,
            &quot;rooms&quot;: 9,
            &quot;bathrooms&quot;: 3,
            &quot;area&quot;: &quot;476.21&quot;,
            &quot;detailed_info&quot;: &quot;Temporibus ipsa beatae dolores. Ex dolorem impedit laborum eum ullam ad nihil. Aspernatur dolores velit sapiente ut cupiditate a vero.\n\nQuia est vitae fugiat voluptatum voluptates. Asperiores quas iste asperiores at sint nam aut quos. Non modi omnis et inventore ipsam sed praesentium.\n\nEligendi et necessitatibus repudiandae et libero sapiente in. Eaque consequatur amet ex laborum voluptatem nostrum maiores. Et accusamus architecto nesciunt voluptatem ex soluta. Est vero sed maxime assumenda et aut consequatur.\n\nDoloribus animi ut ducimus eos neque autem sit velit. Ea mollitia voluptas occaecati excepturi. Optio alias architecto voluptas facilis ad.\n\nError tempore optio molestiae et veniam temporibus animi. Veniam voluptas voluptas rerum quisquam sapiente consequatur numquam. Porro qui dignissimos aperiam quia accusantium. Est eveniet et aspernatur est et tenetur.&quot;,
            &quot;price&quot;: &quot;51720.38&quot;,
            &quot;currency&quot;: &quot;EUR&quot;,
            &quot;formatted_price&quot;: &quot;EUR 51,720.38&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 6,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Mountain Retreat in Berlin&quot;,
            &quot;description&quot;: &quot;Impedit sint sunt quibusdam accusantium earum nihil. Molestiae voluptatibus autem quos voluptatem. Ad molestiae nesciunt porro quis dolorum voluptatem. Quasi illum qui repudiandae debitis dolor.\n\nTempore sed illo quidem quod et. Quia dolorem explicabo voluptate quis dicta error. Hic maxime numquam quo id.\n\nAut ut qui est corrupti ut tempore doloribus. Animi assumenda incidunt eum praesentium voluptates quia tenetur odit. Quia est perspiciatis aut aut ipsa iusto eum repellendus. Est error quo incidunt totam impedit autem et. Magnam pariatur placeat aliquam nisi ipsum iusto necessitatibus.&quot;,
            &quot;country_id&quot;: 5,
            &quot;city_id&quot;: 22,
            &quot;longitude&quot;: &quot;114.91616800&quot;,
            &quot;latitude&quot;: &quot;-40.28238400&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 5,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Germany&quot;,
                &quot;code&quot;: &quot;DEU&quot;,
                &quot;phone_code&quot;: &quot;+49&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 22,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Berlin&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;commercial&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;211.78&quot;,
            &quot;detailed_info&quot;: &quot;Et eius eveniet odio ullam. Eius consequatur consequatur molestias similique numquam velit repudiandae. Voluptatum atque quia est commodi corporis sit et. Corporis temporibus ut vitae et omnis. Nemo id ex tempore.\n\nDolores repellendus rem placeat dolores enim similique. Quam saepe qui dolorem ut dolor. Qui reiciendis porro temporibus corrupti.\n\nModi ipsum iure voluptatem maxime blanditiis laborum quia. Ad sit beatae corrupti non minima.\n\nAccusantium labore labore accusantium dolorum. Dolores qui at quo esse laudantium consectetur. Et est inventore corrupti eum architecto voluptatem aut.\n\nDignissimos recusandae vel fuga magnam voluptas molestiae. Autem quia nam repellat ut.&quot;,
            &quot;price&quot;: &quot;1072674.78&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 1,072,674.78&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 10,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Country Estate in Birmingham&quot;,
            &quot;description&quot;: &quot;Et similique alias enim eaque accusamus repellat praesentium. Est ut ipsa delectus id neque aliquid. Et dicta nesciunt optio. Fugit eius exercitationem libero debitis voluptatem.\n\nAut est velit architecto minima sit. Enim voluptatum non quia. Praesentium assumenda praesentium quisquam voluptates eum quisquam enim. Autem rem eum aut perspiciatis est sint atque. Reprehenderit doloribus vel praesentium.\n\nDelectus consequatur consequatur fuga enim nemo aut eveniet. Tempora earum hic cum expedita non ut unde. Numquam aut vero voluptatem natus repellat.&quot;,
            &quot;country_id&quot;: 2,
            &quot;city_id&quot;: 11,
            &quot;longitude&quot;: &quot;66.18438300&quot;,
            &quot;latitude&quot;: &quot;-24.98462200&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United Kingdom&quot;,
                &quot;code&quot;: &quot;GBR&quot;,
                &quot;phone_code&quot;: &quot;+44&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 11,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Birmingham&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;villa&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 3,
            &quot;area&quot;: &quot;320.86&quot;,
            &quot;detailed_info&quot;: &quot;Aliquid omnis vel accusamus repellat. Et quaerat mollitia ea. Et rerum est et ad et velit sapiente ab.\n\nFugit earum quaerat repudiandae ut explicabo aut. Ut nihil nam harum et. Non eius voluptatem hic et quae.\n\nEt necessitatibus itaque nemo ducimus aut eos. Maxime doloremque debitis qui dolores. Dolor ipsa atque sunt explicabo debitis eum impedit.\n\nEt corrupti et dicta tempora ut. Dolores iure sit alias et. Quo enim voluptates ut quia facere amet.\n\nHarum voluptate autem eligendi quae culpa asperiores vero omnis. Molestiae qui voluptatum voluptas sed deserunt sed. Nostrum quod fugiat et dolorem mollitia harum dignissimos.&quot;,
            &quot;price&quot;: &quot;3885719.88&quot;,
            &quot;currency&quot;: &quot;EUR&quot;,
            &quot;formatted_price&quot;: &quot;EUR 3,885,719.88&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 4,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Boston&quot;,
            &quot;description&quot;: &quot;Et asperiores fugit ratione neque. Aspernatur assumenda doloribus reprehenderit optio laboriosam. Culpa sint suscipit expedita non nemo veniam. Illo repudiandae voluptas beatae voluptatem et quas. Laborum dolores rerum excepturi rerum est inventore voluptatem.\n\nQuae error in temporibus. Quae omnis voluptate aliquam dolore dolor in rerum. Dolorem laborum consequatur sit ut sunt quis molestiae necessitatibus.\n\nTempore eos iste ipsum temporibus aspernatur et commodi. Iure minima voluptas consectetur. Aut assumenda eum quo sapiente rem culpa aliquam. Odit nam consequatur non sint quaerat eligendi unde accusamus.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 8,
            &quot;longitude&quot;: &quot;-136.46901500&quot;,
            &quot;latitude&quot;: &quot;-2.58875800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 8,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Boston&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;commercial&quot;,
            &quot;rooms&quot;: 7,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;222.04&quot;,
            &quot;detailed_info&quot;: &quot;Iure id voluptas natus eaque. Iusto nobis officiis blanditiis recusandae provident illo autem. Pariatur dolor quas nihil sed vitae. Eligendi ex omnis est.\n\nSit natus quos possimus praesentium consectetur. Impedit id nesciunt ut totam id dolore. Voluptates magni doloribus enim.\n\nDolor labore hic sit dolor vel est. Quia qui dolorum est dolore ipsum consequatur qui. Voluptatum qui distinctio minima molestiae. Ipsa dicta tempora perferendis.\n\nIste nulla totam odio. Veritatis inventore suscipit dolorum. Amet fugiat nemo nihil ut. Qui itaque libero quae aut.\n\nEveniet voluptas et accusamus. Qui nemo enim soluta laborum a placeat. Praesentium culpa esse dolorum et doloribus. Expedita magnam laborum laborum tempora.&quot;,
            &quot;price&quot;: &quot;1600249.09&quot;,
            &quot;currency&quot;: &quot;CAD&quot;,
            &quot;formatted_price&quot;: &quot;CAD 1,600,249.09&quot;,
            &quot;status&quot;: &quot;pending&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: null,
            &quot;approved_at&quot;: null,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: null,
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 11,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Modern Apartment in Calgary&quot;,
            &quot;description&quot;: &quot;Voluptate sit quas voluptatem eos. Non nulla ut voluptatem qui. Qui quae iusto porro harum quisquam delectus beatae quasi. Non repellendus sequi expedita similique inventore molestias. Aut enim hic sit deleniti.\n\nSunt ex mollitia aspernatur voluptates deserunt non recusandae. Voluptas non consequatur fugit vitae debitis sequi nemo. Eius aut et ab accusantium vel.\n\nNon quasi provident aut provident nihil ut natus maxime. Quia vero laborum ipsam qui autem deleniti iste non. Officia itaque et voluptas itaque vel aut accusantium. Voluptate expedita error est ut officia.&quot;,
            &quot;country_id&quot;: 3,
            &quot;city_id&quot;: 17,
            &quot;longitude&quot;: &quot;-144.34732400&quot;,
            &quot;latitude&quot;: &quot;-29.02671800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Canada&quot;,
                &quot;code&quot;: &quot;CAN&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 17,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Calgary&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;rent&quot;,
            &quot;property_type&quot;: &quot;land&quot;,
            &quot;rooms&quot;: 2,
            &quot;bathrooms&quot;: 2,
            &quot;area&quot;: &quot;309.30&quot;,
            &quot;detailed_info&quot;: &quot;Quo ad vero consequatur nihil. Id eos expedita qui sed velit et. Quibusdam sapiente harum eligendi.\n\nNesciunt cumque ut occaecati veniam aspernatur voluptas a. Voluptatum ipsa deleniti ullam dolores. Porro et rerum id voluptatem reiciendis sint sit. Sit nulla ipsum aspernatur incidunt libero adipisci.\n\nIure laborum eveniet et incidunt voluptas magni expedita. Est unde a iure et. Quos voluptas aut et autem voluptas ex. Placeat nihil est exercitationem. Est quasi sed quis nostrum reprehenderit veritatis voluptate.\n\nIn in quia optio nemo doloribus eos. Non magni aperiam eos minima alias illum. Iusto quisquam et sequi quis ipsa.\n\nIllum eligendi sint quod quae. Dolorem quis unde optio. Voluptatem voluptatem cumque officia animi et amet fugit.&quot;,
            &quot;price&quot;: &quot;1125331.50&quot;,
            &quot;currency&quot;: &quot;CAD&quot;,
            &quot;formatted_price&quot;: &quot;CAD 1,125,331.50&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 13,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;City Loft in Chicago&quot;,
            &quot;description&quot;: &quot;Cum aut consequatur minima sapiente qui maiores molestiae. Quia consequatur illum nisi. Quis saepe quae ut iusto.\n\nVoluptatem sed iste mollitia facere error. Eligendi sit qui ab repudiandae fuga quae reprehenderit. Doloremque voluptas corrupti ex odit distinctio. Et ipsam corrupti quo nihil et. Maiores quam nulla non molestiae dicta.\n\nNesciunt fuga et aut omnis. Quisquam quam culpa repellat ab fugit perspiciatis. Enim explicabo et at consequatur sunt commodi. Sunt veniam fugiat non neque.&quot;,
            &quot;country_id&quot;: 1,
            &quot;city_id&quot;: 3,
            &quot;longitude&quot;: &quot;36.02937900&quot;,
            &quot;latitude&quot;: &quot;2.45447800&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;United States&quot;,
                &quot;code&quot;: &quot;USA&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Chicago&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;commercial&quot;,
            &quot;rooms&quot;: 4,
            &quot;bathrooms&quot;: 1,
            &quot;area&quot;: &quot;150.30&quot;,
            &quot;detailed_info&quot;: &quot;Dolor corrupti suscipit blanditiis. Consequatur reprehenderit qui officiis ullam ex. Eos ut dolorem praesentium qui aspernatur saepe et. Impedit odio harum vero ducimus.\n\nPraesentium voluptas ea veritatis non. Officiis ut quas perspiciatis aut et minima fugit id. Consectetur nobis veniam incidunt impedit consequatur. Inventore commodi non repellendus laborum deserunt occaecati.\n\nOdio unde in ut earum ut. Aut et officia esse ut maiores dignissimos. Sit magni ipsa incidunt.\n\nVoluptas consequuntur suscipit cum est aut velit aut quia. Sit voluptatem ad rerum omnis et. Sint illo ut voluptas rerum aut soluta voluptate.\n\nRepellat error at quia numquam aut facilis molestias. Consequatur nulla dolor sequi autem nobis non. Eaque a iste dolores ut voluptatem.&quot;,
            &quot;price&quot;: &quot;1593610.75&quot;,
            &quot;currency&quot;: &quot;GBP&quot;,
            &quot;formatted_price&quot;: &quot;GBP 1,593,610.75&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        },
        {
            &quot;id&quot;: 19,
            &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
            &quot;name&quot;: &quot;Cozy Studio in Vancouver&quot;,
            &quot;description&quot;: &quot;Quo incidunt esse et aut. Et expedita impedit rerum dolor. Et quos iure sed aut sit.\n\nSed id et dolores et. Soluta et expedita eveniet aliquid et quis. Quasi ut autem fugit aut id. Architecto natus aspernatur est laudantium.\n\nOmnis ipsum excepturi minus omnis eum reprehenderit omnis sunt. Iste rerum est maiores blanditiis aut omnis. Temporibus quis rerum aut ea nesciunt dolor tenetur.&quot;,
            &quot;country_id&quot;: 3,
            &quot;city_id&quot;: 15,
            &quot;longitude&quot;: &quot;176.01889900&quot;,
            &quot;latitude&quot;: &quot;50.72217400&quot;,
            &quot;country&quot;: {
                &quot;id&quot;: 3,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Canada&quot;,
                &quot;code&quot;: &quot;CAN&quot;,
                &quot;phone_code&quot;: &quot;+1&quot;,
                &quot;is_active&quot;: true,
                &quot;cities_count&quot;: 0
            },
            &quot;city&quot;: {
                &quot;id&quot;: 15,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:22&quot;,
                &quot;name&quot;: &quot;Vancouver&quot;,
                &quot;country_id&quot;: null,
                &quot;state_provianc&quot;: null,
                &quot;postal_code&quot;: null,
                &quot;is_active&quot;: true
            },
            &quot;type_of_contract&quot;: &quot;sale&quot;,
            &quot;property_type&quot;: &quot;office&quot;,
            &quot;rooms&quot;: 4,
            &quot;bathrooms&quot;: 5,
            &quot;area&quot;: &quot;191.85&quot;,
            &quot;detailed_info&quot;: &quot;Voluptas natus praesentium error earum debitis ut voluptas libero. Eius eligendi aut ipsa et exercitationem ut vitae.\n\nAliquam reprehenderit voluptatem aspernatur qui totam. Voluptates error nihil placeat impedit. Voluptatum quasi sed necessitatibus consectetur magni. Pariatur adipisci tenetur non nulla.\n\nPerspiciatis nisi voluptatem eum accusantium aperiam saepe. Rerum velit dolorem esse omnis odit. Laudantium molestiae vero qui.\n\nEt eveniet optio id rerum omnis natus. Impedit quo repudiandae ipsa ad neque et voluptas libero. Animi doloribus quidem accusantium recusandae quia voluptatem corporis.\n\nAdipisci est cupiditate odio molestias. Impedit commodi a quia laboriosam porro in. Voluptates illo perspiciatis rerum optio voluptatem tempora quod. Nemo dolores fuga ut vel nobis vel aut est.&quot;,
            &quot;price&quot;: &quot;3227799.53&quot;,
            &quot;currency&quot;: &quot;USD&quot;,
            &quot;formatted_price&quot;: &quot;USD 3,227,799.53&quot;,
            &quot;status&quot;: &quot;sold&quot;,
            &quot;is_loved&quot;: false,
            &quot;main_image&quot;: &quot;&quot;,
            &quot;main_image_thumb&quot;: &quot;&quot;,
            &quot;gallery&quot;: [],
            &quot;publisher_id&quot;: 1,
            &quot;approved_by&quot;: 2,
            &quot;approved_at&quot;: &quot;2026-03-14 16:26:22&quot;,
            &quot;publisher&quot;: {
                &quot;id&quot;: 1,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Admin&quot;,
                &quot;email&quot;: &quot;admin@admin.com&quot;
            },
            &quot;approver&quot;: {
                &quot;id&quot;: 2,
                &quot;created_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;updated_at&quot;: &quot;2026-04-10 16:26:21&quot;,
                &quot;name&quot;: &quot;Prof. Jean Torphy II&quot;,
                &quot;email&quot;: &quot;mueller.edison@example.org&quot;
            },
            &quot;media&quot;: []
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-properties-random" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-properties-random"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-properties-random"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-properties-random" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-properties-random">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-properties-random" data-method="GET"
      data-path="api/properties/random"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-properties-random', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-properties-random"
                    onclick="tryItOut('GETapi-properties-random');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-properties-random"
                    onclick="cancelTryOut('GETapi-properties-random');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-properties-random"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/properties/random</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-properties-random"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-properties-random"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-properties-random"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-properties--id-">GET api/properties/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-properties--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/properties/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-properties--id-">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;exceptions.server_error&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-properties--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-properties--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-properties--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-properties--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-properties--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-properties--id-" data-method="GET"
      data-path="api/properties/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-properties--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-properties--id-"
                    onclick="tryItOut('GETapi-properties--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-properties--id-"
                    onclick="cancelTryOut('GETapi-properties--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-properties--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/properties/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-properties--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-properties--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties">POST api/properties</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"country_id\": \"consequatur\",
    \"city_id\": \"consequatur\",
    \"longitude\": -180,
    \"latitude\": -89,
    \"property_type\": \"land\",
    \"type_of_contract\": \"sale\",
    \"rooms\": 16,
    \"bathrooms\": 50,
    \"area\": 55,
    \"detailed_info\": \"consequatur\",
    \"price\": 45,
    \"currency\": \"qeo\",
    \"main_image\": {
        \"id\": 17,
        \"temporary_folder\": \"consequatur\"
    },
    \"gallery\": [
        {
            \"id\": 17,
            \"temporary_folder\": \"consequatur\"
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "country_id": "consequatur",
    "city_id": "consequatur",
    "longitude": -180,
    "latitude": -89,
    "property_type": "land",
    "type_of_contract": "sale",
    "rooms": 16,
    "bathrooms": 50,
    "area": 55,
    "detailed_info": "consequatur",
    "price": 45,
    "currency": "qeo",
    "main_image": {
        "id": 17,
        "temporary_folder": "consequatur"
    },
    "gallery": [
        {
            "id": 17,
            "temporary_folder": "consequatur"
        }
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties">
</span>
<span id="execution-results-POSTapi-properties" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties" data-method="POST"
      data-path="api/properties"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties"
                    onclick="tryItOut('POSTapi-properties');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties"
                    onclick="cancelTryOut('POSTapi-properties');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-properties"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-properties"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_id"                data-endpoint="POSTapi-properties"
               value="consequatur"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the countries table. Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>city_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="city_id"                data-endpoint="POSTapi-properties"
               value="consequatur"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the cities table. Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>longitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="longitude"                data-endpoint="POSTapi-properties"
               value="-180"
               data-component="body">
    <br>
<p>Must be between -180 and 180. Example: <code>-180</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>latitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="latitude"                data-endpoint="POSTapi-properties"
               value="-89"
               data-component="body">
    <br>
<p>Must be between -90 and 90. Example: <code>-89</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>property_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="property_type"                data-endpoint="POSTapi-properties"
               value="land"
               data-component="body">
    <br>
<p>Example: <code>land</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>apartment</code></li> <li><code>house</code></li> <li><code>villa</code></li> <li><code>land</code></li> <li><code>commercial</code></li> <li><code>office</code></li> <li><code>warehouse</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type_of_contract</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type_of_contract"                data-endpoint="POSTapi-properties"
               value="sale"
               data-component="body">
    <br>
<p>Example: <code>sale</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>sale</code></li> <li><code>rent</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rooms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="rooms"                data-endpoint="POSTapi-properties"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bathrooms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="bathrooms"                data-endpoint="POSTapi-properties"
               value="50"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>50</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>area</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="area"                data-endpoint="POSTapi-properties"
               value="55"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>55</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>detailed_info</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="detailed_info"                data-endpoint="POSTapi-properties"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="POSTapi-properties"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-properties"
               value="qeo"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>qeo</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>main_image</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="main_image.id"                data-endpoint="POSTapi-properties"
               value="17"
               data-component="body">
    <br>
<p>Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>temporary_folder</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="main_image.temporary_folder"                data-endpoint="POSTapi-properties"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>gallery</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="gallery.0.id"                data-endpoint="POSTapi-properties"
               value="17"
               data-component="body">
    <br>
<p>Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>temporary_folder</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gallery.0.temporary_folder"                data-endpoint="POSTapi-properties"
               value="consequatur"
               data-component="body">
    <br>
<p>The <code>folder</code> of an existing record in the temporary_files table. Example: <code>consequatur</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-properties--id-">PUT api/properties/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-properties--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/properties/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"longitude\": -180,
    \"latitude\": -89,
    \"property_type\": \"warehouse\",
    \"type_of_contract\": \"sale\",
    \"rooms\": 13,
    \"bathrooms\": 65,
    \"area\": 72,
    \"detailed_info\": \"consequatur\",
    \"price\": 45,
    \"currency\": \"qeo\",
    \"status\": \"pending\",
    \"main_image\": {
        \"id\": 17,
        \"temporary_folder\": \"consequatur\"
    },
    \"gallery\": [
        {
            \"id\": 17,
            \"temporary_folder\": \"consequatur\"
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "longitude": -180,
    "latitude": -89,
    "property_type": "warehouse",
    "type_of_contract": "sale",
    "rooms": 13,
    "bathrooms": 65,
    "area": 72,
    "detailed_info": "consequatur",
    "price": 45,
    "currency": "qeo",
    "status": "pending",
    "main_image": {
        "id": 17,
        "temporary_folder": "consequatur"
    },
    "gallery": [
        {
            "id": 17,
            "temporary_folder": "consequatur"
        }
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-properties--id-">
</span>
<span id="execution-results-PUTapi-properties--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-properties--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-properties--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-properties--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-properties--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-properties--id-" data-method="PUT"
      data-path="api/properties/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-properties--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-properties--id-"
                    onclick="tryItOut('PUTapi-properties--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-properties--id-"
                    onclick="cancelTryOut('PUTapi-properties--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-properties--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/properties/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/properties/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-properties--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-properties--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-properties--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-properties--id-"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_id"                data-endpoint="PUTapi-properties--id-"
               value=""
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the countries table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>city_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="city_id"                data-endpoint="PUTapi-properties--id-"
               value=""
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the cities table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>longitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="longitude"                data-endpoint="PUTapi-properties--id-"
               value="-180"
               data-component="body">
    <br>
<p>Must be between -180 and 180. Example: <code>-180</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>latitude</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="latitude"                data-endpoint="PUTapi-properties--id-"
               value="-89"
               data-component="body">
    <br>
<p>Must be between -90 and 90. Example: <code>-89</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>property_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="property_type"                data-endpoint="PUTapi-properties--id-"
               value="warehouse"
               data-component="body">
    <br>
<p>Example: <code>warehouse</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>apartment</code></li> <li><code>house</code></li> <li><code>villa</code></li> <li><code>land</code></li> <li><code>commercial</code></li> <li><code>office</code></li> <li><code>warehouse</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type_of_contract</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type_of_contract"                data-endpoint="PUTapi-properties--id-"
               value="sale"
               data-component="body">
    <br>
<p>Example: <code>sale</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>sale</code></li> <li><code>rent</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rooms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="rooms"                data-endpoint="PUTapi-properties--id-"
               value="13"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>13</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bathrooms</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="bathrooms"                data-endpoint="PUTapi-properties--id-"
               value="65"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>65</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>area</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="area"                data-endpoint="PUTapi-properties--id-"
               value="72"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>72</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>detailed_info</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="detailed_info"                data-endpoint="PUTapi-properties--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="PUTapi-properties--id-"
               value="45"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="PUTapi-properties--id-"
               value="qeo"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>qeo</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-properties--id-"
               value="pending"
               data-component="body">
    <br>
<p>Example: <code>pending</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>approved</code></li> <li><code>rejected</code></li> <li><code>sold</code></li> <li><code>archived</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>main_image</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="main_image.id"                data-endpoint="PUTapi-properties--id-"
               value="17"
               data-component="body">
    <br>
<p>Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>temporary_folder</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="main_image.temporary_folder"                data-endpoint="PUTapi-properties--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>gallery</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="gallery.0.id"                data-endpoint="PUTapi-properties--id-"
               value="17"
               data-component="body">
    <br>
<p>Example: <code>17</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>temporary_folder</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gallery.0.temporary_folder"                data-endpoint="PUTapi-properties--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-properties--id-">DELETE api/properties/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-properties--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://127.0.0.1:8000/api/properties/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-properties--id-">
</span>
<span id="execution-results-DELETEapi-properties--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-properties--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-properties--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-properties--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-properties--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-properties--id-" data-method="DELETE"
      data-path="api/properties/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-properties--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-properties--id-"
                    onclick="tryItOut('DELETEapi-properties--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-properties--id-"
                    onclick="cancelTryOut('DELETEapi-properties--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-properties--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/properties/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-properties--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-properties--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-properties--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties--id--approve">POST api/properties/{id}/approve</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/approve" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/approve"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--approve">
</span>
<span id="execution-results-POSTapi-properties--id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--approve" data-method="POST"
      data-path="api/properties/{id}/approve"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--approve"
                    onclick="tryItOut('POSTapi-properties--id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--approve"
                    onclick="cancelTryOut('POSTapi-properties--id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--approve"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--approve"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties--id--reject">POST api/properties/{id}/reject</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/reject" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/reject"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--reject">
</span>
<span id="execution-results-POSTapi-properties--id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--reject" data-method="POST"
      data-path="api/properties/{id}/reject"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--reject"
                    onclick="tryItOut('POSTapi-properties--id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--reject"
                    onclick="cancelTryOut('POSTapi-properties--id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--reject"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--reject"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties--id--mark-sold">POST api/properties/{id}/mark-sold</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--mark-sold">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/mark-sold" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/mark-sold"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--mark-sold">
</span>
<span id="execution-results-POSTapi-properties--id--mark-sold" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--mark-sold"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--mark-sold"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--mark-sold" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--mark-sold">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--mark-sold" data-method="POST"
      data-path="api/properties/{id}/mark-sold"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--mark-sold', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--mark-sold"
                    onclick="tryItOut('POSTapi-properties--id--mark-sold');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--mark-sold"
                    onclick="cancelTryOut('POSTapi-properties--id--mark-sold');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--mark-sold"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/mark-sold</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--mark-sold"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--mark-sold"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--mark-sold"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--mark-sold"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties--id--archive">POST api/properties/{id}/archive</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--archive">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/archive" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/archive"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--archive">
</span>
<span id="execution-results-POSTapi-properties--id--archive" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--archive"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--archive"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--archive" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--archive">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--archive" data-method="POST"
      data-path="api/properties/{id}/archive"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--archive', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--archive"
                    onclick="tryItOut('POSTapi-properties--id--archive');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--archive"
                    onclick="cancelTryOut('POSTapi-properties--id--archive');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--archive"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/archive</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--archive"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--archive"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--archive"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--archive"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-properties--id--restore">POST api/properties/{id}/restore</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--restore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/restore" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/restore"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--restore">
</span>
<span id="execution-results-POSTapi-properties--id--restore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--restore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--restore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--restore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--restore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--restore" data-method="POST"
      data-path="api/properties/{id}/restore"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--restore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--restore"
                    onclick="tryItOut('POSTapi-properties--id--restore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--restore"
                    onclick="cancelTryOut('POSTapi-properties--id--restore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--restore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/restore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--restore"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--restore"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-properties--id--status">PUT api/properties/{id}/status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-properties--id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/properties/consequatur/status" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"archived\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/status"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "archived"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-properties--id--status">
</span>
<span id="execution-results-PUTapi-properties--id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-properties--id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-properties--id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-properties--id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-properties--id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-properties--id--status" data-method="PUT"
      data-path="api/properties/{id}/status"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-properties--id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-properties--id--status"
                    onclick="tryItOut('PUTapi-properties--id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-properties--id--status"
                    onclick="cancelTryOut('PUTapi-properties--id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-properties--id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/properties/{id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-properties--id--status"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-properties--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-properties--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-properties--id--status"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-properties--id--status"
               value="archived"
               data-component="body">
    <br>
<p>Example: <code>archived</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>approved</code></li> <li><code>rejected</code></li> <li><code>sold</code></li> <li><code>archived</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-properties-statistics">GET api/properties/statistics</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-properties-statistics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/properties/statistics" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/statistics"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-properties-statistics">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;exceptions.server_error&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-properties-statistics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-properties-statistics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-properties-statistics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-properties-statistics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-properties-statistics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-properties-statistics" data-method="GET"
      data-path="api/properties/statistics"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-properties-statistics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-properties-statistics"
                    onclick="tryItOut('GETapi-properties-statistics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-properties-statistics"
                    onclick="cancelTryOut('GETapi-properties-statistics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-properties-statistics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/properties/statistics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-properties-statistics"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-properties-statistics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-properties-statistics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-properties--id--toggle-favorite">POST api/properties/{id}/toggle-favorite</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-properties--id--toggle-favorite">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/properties/consequatur/toggle-favorite" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/properties/consequatur/toggle-favorite"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-properties--id--toggle-favorite">
</span>
<span id="execution-results-POSTapi-properties--id--toggle-favorite" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-properties--id--toggle-favorite"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-properties--id--toggle-favorite"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-properties--id--toggle-favorite" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-properties--id--toggle-favorite">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-properties--id--toggle-favorite" data-method="POST"
      data-path="api/properties/{id}/toggle-favorite"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-properties--id--toggle-favorite', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-properties--id--toggle-favorite"
                    onclick="tryItOut('POSTapi-properties--id--toggle-favorite');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-properties--id--toggle-favorite"
                    onclick="cancelTryOut('POSTapi-properties--id--toggle-favorite');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-properties--id--toggle-favorite"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/properties/{id}/toggle-favorite</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-properties--id--toggle-favorite"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-properties--id--toggle-favorite"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-properties--id--toggle-favorite"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-properties--id--toggle-favorite"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the property. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-categories-public">GET api/categories/public</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-categories-public">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/categories/public" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories/public"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories-public">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;exceptions.server_error&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories-public" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories-public"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories-public"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories-public" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories-public">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories-public" data-method="GET"
      data-path="api/categories/public"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories-public', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories-public"
                    onclick="tryItOut('GETapi-categories-public');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories-public"
                    onclick="cancelTryOut('GETapi-categories-public');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories-public"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories/public</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-categories-public"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories-public"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories-public"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-categories-public--id-">GET api/categories/public/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-categories-public--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/categories/public/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories/public/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories-public--id-">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;exceptions.server_error&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories-public--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories-public--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories-public--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories-public--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories-public--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories-public--id-" data-method="GET"
      data-path="api/categories/public/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories-public--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories-public--id-"
                    onclick="tryItOut('GETapi-categories-public--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories-public--id-"
                    onclick="cancelTryOut('GETapi-categories-public--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories-public--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories/public/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-categories-public--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories-public--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories-public--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-categories-public--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the public. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-categories">GET api/categories</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/categories" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories" data-method="GET"
      data-path="api/categories"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories"
                    onclick="tryItOut('GETapi-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories"
                    onclick="cancelTryOut('GETapi-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-categories"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-categories">POST api/categories</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/categories" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"type\": \"car\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "type": "car"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-categories">
</span>
<span id="execution-results-POSTapi-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-categories" data-method="POST"
      data-path="api/categories"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-categories"
                    onclick="tryItOut('POSTapi-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-categories"
                    onclick="cancelTryOut('POSTapi-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-categories"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-categories"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-categories"
               value="car"
               data-component="body">
    <br>
<p>Example: <code>car</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>property</code></li> <li><code>car</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-categories--id-">GET api/categories/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/categories/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories--id-" data-method="GET"
      data-path="api/categories/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories--id-"
                    onclick="tryItOut('GETapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories--id-"
                    onclick="cancelTryOut('GETapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-categories--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-categories--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-categories--id-">PUT api/categories/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/categories/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"type\": \"car\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "type": "car"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-categories--id-">
</span>
<span id="execution-results-PUTapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-categories--id-" data-method="PUT"
      data-path="api/categories/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-categories--id-"
                    onclick="tryItOut('PUTapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-categories--id-"
                    onclick="cancelTryOut('PUTapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/categories/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-categories--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-categories--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-categories--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PUTapi-categories--id-"
               value="car"
               data-component="body">
    <br>
<p>Example: <code>car</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>property</code></li> <li><code>car</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-categories--id-">DELETE api/categories/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://127.0.0.1:8000/api/categories/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/categories/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-categories--id-">
</span>
<span id="execution-results-DELETEapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-categories--id-" data-method="DELETE"
      data-path="api/categories/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-categories--id-"
                    onclick="tryItOut('DELETEapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-categories--id-"
                    onclick="cancelTryOut('DELETEapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-categories--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-categories--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-auth-register">Create a new registered user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/register" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/register"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-register">
</span>
<span id="execution-results-POSTapi-auth-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-register" data-method="POST"
      data-path="api/auth/register"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-register"
                    onclick="tryItOut('POSTapi-auth-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-register"
                    onclick="cancelTryOut('POSTapi-auth-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-register"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-login">Attempt to authenticate a new session.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/login" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/login"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com",
    "password": "O[2UZ5ij-e\/dl4m{o,"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-login">
</span>
<span id="execution-results-POSTapi-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-login" data-method="POST"
      data-path="api/auth/login"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-login"
                    onclick="tryItOut('POSTapi-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-login"
                    onclick="cancelTryOut('POSTapi-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-login"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-login"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>Example: <code>qkunze@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-auth-login"
               value="O[2UZ5ij-e/dl4m{o,"
               data-component="body">
    <br>
<p>Example: <code>O[2UZ5ij-e/dl4m{o,</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-two-factor-challenge">Attempt to authenticate a new session using the two factor authentication code.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-two-factor-challenge">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/two-factor-challenge" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"code\": \"consequatur\",
    \"recovery_code\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/two-factor-challenge"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "code": "consequatur",
    "recovery_code": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-two-factor-challenge">
</span>
<span id="execution-results-POSTapi-auth-two-factor-challenge" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-two-factor-challenge"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-two-factor-challenge"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-two-factor-challenge" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-two-factor-challenge">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-two-factor-challenge" data-method="POST"
      data-path="api/auth/two-factor-challenge"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-two-factor-challenge', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-two-factor-challenge"
                    onclick="tryItOut('POSTapi-auth-two-factor-challenge');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-two-factor-challenge"
                    onclick="cancelTryOut('POSTapi-auth-two-factor-challenge');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-two-factor-challenge"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/two-factor-challenge</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-two-factor-challenge"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-two-factor-challenge"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-two-factor-challenge"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-auth-two-factor-challenge"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>recovery_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="recovery_code"                data-endpoint="POSTapi-auth-two-factor-challenge"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-forgot-password">Send a reset link to the given user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-forgot-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/forgot-password" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/forgot-password"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-forgot-password">
</span>
<span id="execution-results-POSTapi-auth-forgot-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-forgot-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-forgot-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-forgot-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-forgot-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-forgot-password" data-method="POST"
      data-path="api/auth/forgot-password"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-forgot-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-forgot-password"
                    onclick="tryItOut('POSTapi-auth-forgot-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-forgot-password"
                    onclick="cancelTryOut('POSTapi-auth-forgot-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-forgot-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/forgot-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-forgot-password"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-forgot-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-forgot-password"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>qkunze@example.com</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-reset-password">Reset the user&#039;s password.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-reset-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/reset-password" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"token\": \"consequatur\",
    \"password\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/reset-password"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "token": "consequatur",
    "password": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-reset-password">
</span>
<span id="execution-results-POSTapi-auth-reset-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-reset-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-reset-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-reset-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-reset-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-reset-password" data-method="POST"
      data-path="api/auth/reset-password"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-reset-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-reset-password"
                    onclick="tryItOut('POSTapi-auth-reset-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-reset-password"
                    onclick="cancelTryOut('POSTapi-auth-reset-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-reset-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/reset-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-reset-password"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-reset-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-auth-reset-password"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-auth-reset-password"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-auth-email-verify--id---hash-">Mark the authenticated user&#039;s email address as verified.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-auth-email-verify--id---hash-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/auth/email/verify/consequatur/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/email/verify/consequatur/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-email-verify--id---hash-">
            <blockquote>
            <p>Example response (500):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 6
x-ratelimit-remaining: 5
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;exceptions.server_error&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-email-verify--id---hash-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-email-verify--id---hash-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-email-verify--id---hash-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-email-verify--id---hash-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-email-verify--id---hash-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-email-verify--id---hash-" data-method="GET"
      data-path="api/auth/email/verify/{id}/{hash}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-email-verify--id---hash-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-email-verify--id---hash-"
                    onclick="tryItOut('GETapi-auth-email-verify--id---hash-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-email-verify--id---hash-"
                    onclick="cancelTryOut('GETapi-auth-email-verify--id---hash-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-email-verify--id---hash-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/email/verify/{id}/{hash}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-auth-email-verify--id---hash-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-email-verify--id---hash-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-email-verify--id---hash-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-auth-email-verify--id---hash-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the verify. Example: <code>consequatur</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>hash</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="hash"                data-endpoint="GETapi-auth-email-verify--id---hash-"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-auth-logout">Destroy an authenticated session.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/logout" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/logout"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-logout">
</span>
<span id="execution-results-POSTapi-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-logout" data-method="POST"
      data-path="api/auth/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-logout"
                    onclick="tryItOut('POSTapi-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-logout"
                    onclick="cancelTryOut('POSTapi-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-logout"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-email-verification-notification">Send a new email verification notification.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-email-verification-notification">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/email/verification-notification" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/email/verification-notification"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-email-verification-notification">
</span>
<span id="execution-results-POSTapi-auth-email-verification-notification" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-email-verification-notification"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-email-verification-notification"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-email-verification-notification" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-email-verification-notification">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-email-verification-notification" data-method="POST"
      data-path="api/auth/email/verification-notification"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-email-verification-notification', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-email-verification-notification"
                    onclick="tryItOut('POSTapi-auth-email-verification-notification');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-email-verification-notification"
                    onclick="cancelTryOut('POSTapi-auth-email-verification-notification');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-email-verification-notification"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/email/verification-notification</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-email-verification-notification"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-email-verification-notification"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-email-verification-notification"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-PUTapi-auth-user-profile-information">Update the user&#039;s profile information.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-auth-user-profile-information">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/auth/user/profile-information" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/profile-information"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-auth-user-profile-information">
</span>
<span id="execution-results-PUTapi-auth-user-profile-information" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-auth-user-profile-information"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-auth-user-profile-information"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-auth-user-profile-information" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-auth-user-profile-information">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-auth-user-profile-information" data-method="PUT"
      data-path="api/auth/user/profile-information"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-auth-user-profile-information', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-auth-user-profile-information"
                    onclick="tryItOut('PUTapi-auth-user-profile-information');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-auth-user-profile-information"
                    onclick="cancelTryOut('PUTapi-auth-user-profile-information');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-auth-user-profile-information"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/auth/user/profile-information</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-auth-user-profile-information"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-auth-user-profile-information"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-auth-user-profile-information"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-PUTapi-auth-user-password">Update the user&#039;s password.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-auth-user-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/auth/user/password" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/password"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-auth-user-password">
</span>
<span id="execution-results-PUTapi-auth-user-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-auth-user-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-auth-user-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-auth-user-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-auth-user-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-auth-user-password" data-method="PUT"
      data-path="api/auth/user/password"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-auth-user-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-auth-user-password"
                    onclick="tryItOut('PUTapi-auth-user-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-auth-user-password"
                    onclick="cancelTryOut('PUTapi-auth-user-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-auth-user-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/auth/user/password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-auth-user-password"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-auth-user-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-auth-user-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-user-confirm-password">Confirm the user&#039;s password.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-user-confirm-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/user/confirm-password" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/confirm-password"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-user-confirm-password">
</span>
<span id="execution-results-POSTapi-auth-user-confirm-password" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-user-confirm-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-user-confirm-password"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-user-confirm-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-user-confirm-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-user-confirm-password" data-method="POST"
      data-path="api/auth/user/confirm-password"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-user-confirm-password', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-user-confirm-password"
                    onclick="tryItOut('POSTapi-auth-user-confirm-password');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-user-confirm-password"
                    onclick="cancelTryOut('POSTapi-auth-user-confirm-password');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-user-confirm-password"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/user/confirm-password</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-user-confirm-password"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-user-confirm-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-user-confirm-password"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-user-two-factor-authentication">Enable two factor authentication for the user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-user-two-factor-authentication">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/user/two-factor-authentication" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-authentication"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-user-two-factor-authentication">
</span>
<span id="execution-results-POSTapi-auth-user-two-factor-authentication" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-user-two-factor-authentication"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-user-two-factor-authentication"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-user-two-factor-authentication" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-user-two-factor-authentication">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-user-two-factor-authentication" data-method="POST"
      data-path="api/auth/user/two-factor-authentication"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-user-two-factor-authentication', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-user-two-factor-authentication"
                    onclick="tryItOut('POSTapi-auth-user-two-factor-authentication');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-user-two-factor-authentication"
                    onclick="cancelTryOut('POSTapi-auth-user-two-factor-authentication');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-user-two-factor-authentication"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/user/two-factor-authentication</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-user-two-factor-authentication"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-user-two-factor-authentication"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-user-two-factor-authentication"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-DELETEapi-auth-user-two-factor-authentication">Disable two factor authentication for the user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-auth-user-two-factor-authentication">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://127.0.0.1:8000/api/auth/user/two-factor-authentication" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-authentication"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-auth-user-two-factor-authentication">
</span>
<span id="execution-results-DELETEapi-auth-user-two-factor-authentication" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-auth-user-two-factor-authentication"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-auth-user-two-factor-authentication"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-auth-user-two-factor-authentication" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-auth-user-two-factor-authentication">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-auth-user-two-factor-authentication" data-method="DELETE"
      data-path="api/auth/user/two-factor-authentication"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-auth-user-two-factor-authentication', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-auth-user-two-factor-authentication"
                    onclick="tryItOut('DELETEapi-auth-user-two-factor-authentication');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-auth-user-two-factor-authentication"
                    onclick="cancelTryOut('DELETEapi-auth-user-two-factor-authentication');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-auth-user-two-factor-authentication"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/auth/user/two-factor-authentication</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-auth-user-two-factor-authentication"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-auth-user-two-factor-authentication"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-auth-user-two-factor-authentication"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-auth-user-two-factor-qr-code">Get the SVG element for the user&#039;s two factor authentication QR code.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-auth-user-two-factor-qr-code">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/auth/user/two-factor-qr-code" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-qr-code"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-user-two-factor-qr-code">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-user-two-factor-qr-code" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-user-two-factor-qr-code"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-user-two-factor-qr-code"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-user-two-factor-qr-code" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-user-two-factor-qr-code">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-user-two-factor-qr-code" data-method="GET"
      data-path="api/auth/user/two-factor-qr-code"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-user-two-factor-qr-code', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-user-two-factor-qr-code"
                    onclick="tryItOut('GETapi-auth-user-two-factor-qr-code');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-user-two-factor-qr-code"
                    onclick="cancelTryOut('GETapi-auth-user-two-factor-qr-code');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-user-two-factor-qr-code"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/user/two-factor-qr-code</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-auth-user-two-factor-qr-code"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-user-two-factor-qr-code"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-user-two-factor-qr-code"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-auth-user-two-factor-secret-key">Get the current user&#039;s two factor authentication setup / secret key.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-auth-user-two-factor-secret-key">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/auth/user/two-factor-secret-key" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-secret-key"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-user-two-factor-secret-key">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-user-two-factor-secret-key" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-user-two-factor-secret-key"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-user-two-factor-secret-key"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-user-two-factor-secret-key" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-user-two-factor-secret-key">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-user-two-factor-secret-key" data-method="GET"
      data-path="api/auth/user/two-factor-secret-key"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-user-two-factor-secret-key', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-user-two-factor-secret-key"
                    onclick="tryItOut('GETapi-auth-user-two-factor-secret-key');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-user-two-factor-secret-key"
                    onclick="cancelTryOut('GETapi-auth-user-two-factor-secret-key');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-user-two-factor-secret-key"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/user/two-factor-secret-key</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-auth-user-two-factor-secret-key"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-user-two-factor-secret-key"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-user-two-factor-secret-key"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-auth-user-two-factor-recovery-codes">Get the two factor authentication recovery codes for authenticated user.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-auth-user-two-factor-recovery-codes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/auth/user/two-factor-recovery-codes" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-recovery-codes"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-user-two-factor-recovery-codes">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-user-two-factor-recovery-codes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-user-two-factor-recovery-codes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-user-two-factor-recovery-codes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-user-two-factor-recovery-codes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-user-two-factor-recovery-codes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-user-two-factor-recovery-codes" data-method="GET"
      data-path="api/auth/user/two-factor-recovery-codes"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-user-two-factor-recovery-codes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-user-two-factor-recovery-codes"
                    onclick="tryItOut('GETapi-auth-user-two-factor-recovery-codes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-user-two-factor-recovery-codes"
                    onclick="cancelTryOut('GETapi-auth-user-two-factor-recovery-codes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-user-two-factor-recovery-codes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/user/two-factor-recovery-codes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-auth-user-two-factor-recovery-codes"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-user-two-factor-recovery-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-user-two-factor-recovery-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-user-two-factor-recovery-codes">Generate a fresh set of two factor authentication recovery codes.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-auth-user-two-factor-recovery-codes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/auth/user/two-factor-recovery-codes" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/auth/user/two-factor-recovery-codes"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-user-two-factor-recovery-codes">
</span>
<span id="execution-results-POSTapi-auth-user-two-factor-recovery-codes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-user-two-factor-recovery-codes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-user-two-factor-recovery-codes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-user-two-factor-recovery-codes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-user-two-factor-recovery-codes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-user-two-factor-recovery-codes" data-method="POST"
      data-path="api/auth/user/two-factor-recovery-codes"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-user-two-factor-recovery-codes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-user-two-factor-recovery-codes"
                    onclick="tryItOut('POSTapi-auth-user-two-factor-recovery-codes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-user-two-factor-recovery-codes"
                    onclick="cancelTryOut('POSTapi-auth-user-two-factor-recovery-codes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-user-two-factor-recovery-codes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/user/two-factor-recovery-codes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-auth-user-two-factor-recovery-codes"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-user-two-factor-recovery-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-user-two-factor-recovery-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-location-cities">GET api/location/cities</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-location-cities">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/location/cities" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/cities"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-location-cities">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-location-cities" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-location-cities"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-location-cities"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-location-cities" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-location-cities">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-location-cities" data-method="GET"
      data-path="api/location/cities"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-location-cities', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-location-cities"
                    onclick="tryItOut('GETapi-location-cities');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-location-cities"
                    onclick="cancelTryOut('GETapi-location-cities');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-location-cities"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/location/cities</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-location-cities"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-location-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-location-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-location-cities">POST api/location/cities</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-location-cities">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/location/cities" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"country_id\": \"consequatur\",
    \"state_provianc\": \"consequatur\",
    \"postal_code\": \"consequatur\",
    \"is_active\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/cities"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "country_id": "consequatur",
    "state_provianc": "consequatur",
    "postal_code": "consequatur",
    "is_active": false
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-location-cities">
</span>
<span id="execution-results-POSTapi-location-cities" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-location-cities"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-location-cities"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-location-cities" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-location-cities">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-location-cities" data-method="POST"
      data-path="api/location/cities"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-location-cities', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-location-cities"
                    onclick="tryItOut('POSTapi-location-cities');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-location-cities"
                    onclick="cancelTryOut('POSTapi-location-cities');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-location-cities"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/location/cities</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-location-cities"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-location-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-location-cities"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-location-cities"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 128 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_id"                data-endpoint="POSTapi-location-cities"
               value="consequatur"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the countries table. Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>state_provianc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="state_provianc"                data-endpoint="POSTapi-location-cities"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>postal_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="postal_code"                data-endpoint="POSTapi-location-cities"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_active</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-location-cities" style="display: none">
            <input type="radio" name="is_active"
                   value="true"
                   data-endpoint="POSTapi-location-cities"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-location-cities" style="display: none">
            <input type="radio" name="is_active"
                   value="false"
                   data-endpoint="POSTapi-location-cities"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-location-cities--id-">GET api/location/cities/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-location-cities--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/location/cities/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/cities/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-location-cities--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-location-cities--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-location-cities--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-location-cities--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-location-cities--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-location-cities--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-location-cities--id-" data-method="GET"
      data-path="api/location/cities/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-location-cities--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-location-cities--id-"
                    onclick="tryItOut('GETapi-location-cities--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-location-cities--id-"
                    onclick="cancelTryOut('GETapi-location-cities--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-location-cities--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/location/cities/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-location-cities--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-location-cities--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the city. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-location-cities--id-">PUT api/location/cities/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-location-cities--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/location/cities/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"country_id\": \"consequatur\",
    \"state_provianc\": \"consequatur\",
    \"postal_code\": \"consequatur\",
    \"is_active\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/cities/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "country_id": "consequatur",
    "state_provianc": "consequatur",
    "postal_code": "consequatur",
    "is_active": true
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-location-cities--id-">
</span>
<span id="execution-results-PUTapi-location-cities--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-location-cities--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-location-cities--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-location-cities--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-location-cities--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-location-cities--id-" data-method="PUT"
      data-path="api/location/cities/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-location-cities--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-location-cities--id-"
                    onclick="tryItOut('PUTapi-location-cities--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-location-cities--id-"
                    onclick="cancelTryOut('PUTapi-location-cities--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-location-cities--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/location/cities/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/location/cities/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-location-cities--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-location-cities--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the city. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-location-cities--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 128 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_id"                data-endpoint="PUTapi-location-cities--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the countries table. Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>state_provianc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="state_provianc"                data-endpoint="PUTapi-location-cities--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>postal_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="postal_code"                data-endpoint="PUTapi-location-cities--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_active</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-location-cities--id-" style="display: none">
            <input type="radio" name="is_active"
                   value="true"
                   data-endpoint="PUTapi-location-cities--id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-location-cities--id-" style="display: none">
            <input type="radio" name="is_active"
                   value="false"
                   data-endpoint="PUTapi-location-cities--id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-location-cities--id-">DELETE api/location/cities/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-location-cities--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://127.0.0.1:8000/api/location/cities/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/cities/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-location-cities--id-">
</span>
<span id="execution-results-DELETEapi-location-cities--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-location-cities--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-location-cities--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-location-cities--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-location-cities--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-location-cities--id-" data-method="DELETE"
      data-path="api/location/cities/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-location-cities--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-location-cities--id-"
                    onclick="tryItOut('DELETEapi-location-cities--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-location-cities--id-"
                    onclick="cancelTryOut('DELETEapi-location-cities--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-location-cities--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/location/cities/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-location-cities--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-location-cities--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-location-cities--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the city. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-location-countries">GET api/location/countries</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-location-countries">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/location/countries" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/countries"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-location-countries">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-location-countries" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-location-countries"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-location-countries"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-location-countries" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-location-countries">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-location-countries" data-method="GET"
      data-path="api/location/countries"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-location-countries', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-location-countries"
                    onclick="tryItOut('GETapi-location-countries');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-location-countries"
                    onclick="cancelTryOut('GETapi-location-countries');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-location-countries"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/location/countries</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-location-countries"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-location-countries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-location-countries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-location-countries">POST api/location/countries</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-location-countries">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://127.0.0.1:8000/api/location/countries" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"code\": \"amn\",
    \"phone_code\": \"iihf\",
    \"is_active\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/countries"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "code": "amn",
    "phone_code": "iihf",
    "is_active": true
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-location-countries">
</span>
<span id="execution-results-POSTapi-location-countries" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-location-countries"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-location-countries"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-location-countries" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-location-countries">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-location-countries" data-method="POST"
      data-path="api/location/countries"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-location-countries', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-location-countries"
                    onclick="tryItOut('POSTapi-location-countries');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-location-countries"
                    onclick="cancelTryOut('POSTapi-location-countries');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-location-countries"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/location/countries</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-location-countries"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-location-countries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-location-countries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-location-countries"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 128 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-location-countries"
               value="amn"
               data-component="body">
    <br>
<p>Must not be greater than 3 characters. Example: <code>amn</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone_code"                data-endpoint="POSTapi-location-countries"
               value="iihf"
               data-component="body">
    <br>
<p>Must not be greater than 4 characters. Example: <code>iihf</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_active</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-location-countries" style="display: none">
            <input type="radio" name="is_active"
                   value="true"
                   data-endpoint="POSTapi-location-countries"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-location-countries" style="display: none">
            <input type="radio" name="is_active"
                   value="false"
                   data-endpoint="POSTapi-location-countries"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-location-countries--id-">GET api/location/countries/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-location-countries--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://127.0.0.1:8000/api/location/countries/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/countries/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-location-countries--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;You are not authenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;data&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-location-countries--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-location-countries--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-location-countries--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-location-countries--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-location-countries--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-location-countries--id-" data-method="GET"
      data-path="api/location/countries/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-location-countries--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-location-countries--id-"
                    onclick="tryItOut('GETapi-location-countries--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-location-countries--id-"
                    onclick="cancelTryOut('GETapi-location-countries--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-location-countries--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/location/countries/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-location-countries--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-location-countries--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the country. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-location-countries--id-">PUT api/location/countries/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-location-countries--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://127.0.0.1:8000/api/location/countries/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"code\": \"amn\",
    \"phone_code\": \"iihf\",
    \"is_active\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/countries/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "code": "amn",
    "phone_code": "iihf",
    "is_active": false
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-location-countries--id-">
</span>
<span id="execution-results-PUTapi-location-countries--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-location-countries--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-location-countries--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-location-countries--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-location-countries--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-location-countries--id-" data-method="PUT"
      data-path="api/location/countries/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-location-countries--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-location-countries--id-"
                    onclick="tryItOut('PUTapi-location-countries--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-location-countries--id-"
                    onclick="cancelTryOut('PUTapi-location-countries--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-location-countries--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/location/countries/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/location/countries/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-location-countries--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-location-countries--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the country. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-location-countries--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 128 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="PUTapi-location-countries--id-"
               value="amn"
               data-component="body">
    <br>
<p>Must not be greater than 3 characters. Example: <code>amn</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone_code"                data-endpoint="PUTapi-location-countries--id-"
               value="iihf"
               data-component="body">
    <br>
<p>Must not be greater than 4 characters. Example: <code>iihf</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_active</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-location-countries--id-" style="display: none">
            <input type="radio" name="is_active"
                   value="true"
                   data-endpoint="PUTapi-location-countries--id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-location-countries--id-" style="display: none">
            <input type="radio" name="is_active"
                   value="false"
                   data-endpoint="PUTapi-location-countries--id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-location-countries--id-">DELETE api/location/countries/{id}</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-location-countries--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://127.0.0.1:8000/api/location/countries/consequatur" \
    --header "Authorization: Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://127.0.0.1:8000/api/location/countries/consequatur"
);

const headers = {
    "Authorization": "Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-location-countries--id-">
</span>
<span id="execution-results-DELETEapi-location-countries--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-location-countries--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-location-countries--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-location-countries--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-location-countries--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-location-countries--id-" data-method="DELETE"
      data-path="api/location/countries/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-location-countries--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-location-countries--id-"
                    onclick="tryItOut('DELETEapi-location-countries--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-location-countries--id-"
                    onclick="cancelTryOut('DELETEapi-location-countries--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-location-countries--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/location/countries/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-location-countries--id-"
               value="Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382"
               data-component="header">
    <br>
<p>Example: <code>Bearer 14|gLxqLsZPXA3iPR6M5GGWMqoNZn494yCoti2IqnKs971c1382</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-location-countries--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-location-countries--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the country. Example: <code>consequatur</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>

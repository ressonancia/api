@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('img/logo-text.png'))) }}" alt="{{ config('app.name') }}" style="max-height: 40px; width: auto;">
</a>
</td>
</tr>

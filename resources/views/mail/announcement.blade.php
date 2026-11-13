<x-mail::message>
# {{ $announcement->title }}

{{-- Escaped, then rendered as Markdown: the body may hold links and inline code, never HTML. --}}
{{ $announcement->body }}

<x-mail::button :url="$announcement->url">
Read more
</x-mail::button>

You're getting this because you are an administrator of the Convoy panel at {{ config('app.url') }}, and the Convoy team published an announcement that applies to it. Every announcement, and why it does or doesn't apply, is under [Settings → Updates]({{ $updatesUrl }}).
</x-mail::message>

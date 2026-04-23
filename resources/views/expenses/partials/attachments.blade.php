@if ($files->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mt-2">
        @foreach ($files as $file)
            @if ($file->is_image)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" target="_blank" class="d-inline-block">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" alt="Adjunto"
                        style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid #e6e8ec;">
                </a>
            @else
                <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" target="_blank" download
                    class="badge badge-light-primary text-primary">
                    PDF
                </a>
            @endif
        @endforeach
    </div>
@endif

<div class="m-1">
    <div class="text-right mb-1 w-full">
        <span class="text-indigo-500">Total [<b>{{ count($files) }}</b>] files</span>
    </div>

    @foreach ($files as $file)
        <div>
            <div class="flex space-x-1">
                <span class="font-bold">{{ $file->file->filename }}</span>
                <span class="text-gray">[{{ str_replace($outputDir, '', dirname($file->file->name)) }}]</span>
                <span class="flex-1 content-repeat-[.] text-gray"></span>
                <span class="text-green">{{ $file->status->name }}</span>
            </div>
        </div>
    @endforeach
</div>

<?php

namespace App\Http\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ImageTrait
{
    protected string $disk = 'r2'; // change to 'public' if needed

    function getImageUrl($value, $folder)
    {
        if($value){
            if (Str::startsWith($value, ['https://', 'http://'])) {
                return $value;
            }
            return url('images/'.$folder.'/'.$value);
        }else{
            return url('images/place_holder/default.png');
        }
    }

    function addImage($file, $folder, $oldFile = null, $parentFolder = null, $fileName = null): string
    {
        try{

            if ($oldFile) {
                $this->deleteImage($oldFile, $folder);
            }

            // Handle wrong input early
//            if (!$file instanceof UploadedFile) {
//                \Log::info('File must be an instance of UploadedFile');
//                throw new \Exception('File must be an instance of UploadedFile');
//            }

            $extension = $file->getClientOriginalExtension() ?: 'bin';

            $time = now()->timestamp . '_' . Str::random(10);
            $fileName = $fileName ?? ($time . '.' . $extension);

            $path = ($parentFolder ? $parentFolder . '/' : '') . $folder;
            $fullPath = ($parentFolder ? $parentFolder . '/' : '') . $folder . '/' . $fileName; // ✅ full path with filename
//            try{
//                \Log::info('upload debug', [
//                    'size' => $file->getSize(),
////                    'realPath' => $file->getRealPath(),
////                    'realPathExists' => file_exists($file->getRealPath()),
//                    'mimeType' => $file->getMimeType(),
//                    'path' => $path,
//                    'fileName' => $fileName,
//                ]);
//            }catch (\Exception $e){
//                \Log::info($e->getMessage());
//            }
//            Storage::disk($this->disk)->putFileAs(
//                $path,
//                $file,
//                $fileName,
//                ['visibility' => 'public']
//            );
            $stream = fopen($file->getRealPath(), 'r');

            Storage::disk($this->disk)->put(
                $fullPath,
                $stream,
                ['visibility' => 'public']
            );

            if (is_resource($stream)) {
                fclose($stream);
            }

            return Storage::disk($this->disk)->url($path . '/' . $fileName);
        }catch (\Exception $e){
            \Log::info($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    function addLocalImage($image,$folder,$oldImage = null,$parentFolder = null,$fileName = null) :String{
        if($oldImage){
            $this->deleteImage($oldImage,$folder);
        }
        $extension = $image->getClientOriginalName();
        if(Str::endsWith($extension, 'blob')){
            if(!Str::endsWith($extension, '.blob')){
                $extension = '.blob';
            }
        }
        $time = intval(microtime(true) * 1000000);
        $fileName = $fileName??($time.'_'.$folder.$extension);
        $image->move(public_path(($parentFolder??"images").'/'.$folder),$fileName);
        return $fileName;
    }

    function deleteImage($image, $folder): void
    {
        if (!$image) return;

        if (Str::startsWith($image, ['https://', 'http://'])) {
            $publicBase = rtrim(config('filesystems.disks.' . $this->disk . '.url'), '/');
            $path = ltrim(str_replace($publicBase, '', $image), '/');

//            \Log::info('deleteImage debug', [
//                'image' => $image,
//                'publicBase' => $publicBase,
//                'resolvedPath' => $path,
//                'exists' => Storage::disk($this->disk)->exists($path),
//            ]);

            if ($path && Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        } else {
            $path = public_path('images/' . $folder . '/' . $image);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

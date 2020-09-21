<?php
namespace App\Classes\UploadImage;

trait ModelUploader {

    public function uploadImage()
    {
        if (!$this->file_name) {
            throw new \InvalidArgumentException("'file_name' property undefined in a model!");
        }

        if (request()->file($this->file_name)) {
            $file = FileUploader::upload([
                'file' => request()->file('avatar'),
                'owner' => self::class,
                'has_image' => $this->{$this->file_name}
            ]);
            $this->{$this->file_name} = !is_array($file) ? $file : implode(',', $file);
            $this->save();
        }
    }
}

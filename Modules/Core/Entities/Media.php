<?php

namespace Modules\Core\Entities;

use Illuminate\Http\UploadedFile;
use Modules\Core\Helpers\File;
use Modules\Core\Helpers\Helpers;
use Shetabit\Shopit\Modules\Core\Entities\Media as BaseMedia;

class Media extends BaseMedia
{
    public static function addMedia($images, $model, $collectionName)
    {
      
        $order = 1;
        foreach ($images as $image){
            if (strlen($image) < 100 && is_numeric($image)) {

                /**
                 * @var $media \Spatie\MediaLibrary\MediaCollections\Models\Media
                 */
                $media = Media::find($image);
                if (!$media) {
                    continue;
                }
                $newMedia = $media->replicate();
                $newMedia->collection_name = $collectionName;
                $newMedia->model()->associate($model)->save();

                $mediaCollection = $model->getMediaCollection($collectionName);
                if ($mediaCollection->singleFile) {
                    $model->media()->where('collection_name', $collectionName)
                        ->whereKeyNot($newMedia->id)->delete();
                        
                }
            }
            elseif($collectionName == 'video'){
                $model->addMediaFromBase64($image)->setOrder($order++)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);
            }
            else if (Helpers::isStringBase64($image, $model::ACCEPTED_IMAGE_MIMES)) {
                $model->addMediaFromBase64($image)->setOrder($order++)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);
            } elseif(\File::isFile($image)) {
                /**
                 * @var $model Product
                 */
                $model->addMedia($image)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);
            }
        }
    }
    public static function updateMedia($images  , $model, $collectionName): array
    {
        $updatedImages= [];
        $order = 1;
        foreach ($images as $image){
            $acceptedImageMimes = defined(get_class($model) . '::' . 'ACCEPTED_IMAGE_MIMES')
                ? $model::ACCEPTED_IMAGE_MIMES : 'gif|png|jpg|jpeg';
            if ($image instanceof UploadedFile) {
                $tempMedia = $model->addMedia($image)->setOrder($order++)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);

                $updatedImages[] = $tempMedia->id;
            }
            elseif($collectionName == 'video'){
                $tempMedia = $model->addMediaFromBase64($image)->setOrder($order++)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);
                    $updatedImages[] = $tempMedia->id;
            }
             elseif (Helpers::isStringBase64($image, $acceptedImageMimes)) {
                $tempMedia = $model->addMediaFromBase64($image)->setOrder($order++)
                    ->withCustomProperties(['type' => class_basename($model)])
                    ->toMediaCollection($collectionName);

                $updatedImages[] = $tempMedia->id;
            } else {
                /**
                 * @var $media \Spatie\MediaLibrary\MediaCollections\Models\Media
                 */
                if ($media = \Modules\Core\Entities\Media::find($image)) {
                    $updatedImages[] = $media->getKey();
                    $media->order_column = $order++;
                    $media->save();
                    continue;
                }
                $media = Media::find($image);
                if (!$media) {
                    continue;
                }
                if($mediaFromModel = $model->media()->where('uuid', $media->uuid)->first()) {
                    $mediaFromModel->order_column = $order++;
                    $mediaFromModel->collection_name = $collectionName;
                    $mediaFromModel->custom_properties = ['type' => class_basename($model)];
                    $mediaFromModel->save();
                    $updatedImages[] = $mediaFromModel->getKey();
                    continue;
                }

                $newMedia = $media->replicate();
                $newMedia->order_column = $order++;
                $newMedia->collection_name = $collectionName;
                $newMedia->custom_properties = ['type' => class_basename($model)];
                $newMedia->model()->associate($model)->save();
                $updatedImages[] = $newMedia->getKey();
            }
        }
        $model->load('media');
        return $updatedImages;
    }

   
}

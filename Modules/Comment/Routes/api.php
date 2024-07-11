<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * Comment
 * post/1/comment
 */

Route::superGroup('front', function (){
    Route::post('/{commentable_type}/{commentable_id}/comments', function(Request $request) {
        foreach (config('comment.commented_models') as $key => $model) {
            if ($key !== $request->route('commentable_type')) {
                continue;
            }
            if ((new $model) instanceof \Modules\Comment\Entities\HasComment) {
                $item = $model::findOrFail($request->route()->parameters['commentable_id']);

                return app()->call(
                    \Modules\Comment\Http\Controllers\CommentController::class .'@store',
                    ['model' => $item]);
            }
        }
    });// store

    Route::get('/{commentable_type}/{commentable_id}/comments', function(Request $request) {
        foreach (config('comment.commented_models') as $key => $model) {
            if ($key !== $request->route('commentable_type')) {
                continue;
            }
            if ((new $model) instanceof \Modules\Comment\Entities\HasComment) {
                $item = $model::findOrFail($request->route()->parameters['commentable_id']);

                return app()->call(
                    \Modules\Comment\Http\Controllers\CommentController::class .'@index',
                    ['model' => $item]);
            }
        }
    }); // index
},[]);



Route::superGroup('admin', function () {
    Route::get('/{commentable_type}/{commentable_id}/comments', function(Request $request) {
        foreach (config('comment.commented_models') as $key => $model) {
            if ($key !== $request->route('commentable_type')) {
                continue;
            }
            if ((new $model) instanceof \Modules\Comment\Entities\HasComment) {
                $item = $model::findOrFail($request->route()->parameters['commentable_id']);

                return app()->call(
                    \Modules\Comment\Http\Controllers\Admin\CommentController::class . '@index',
                    ['model' => $item]);
            }
        }
    });

    Route::get('comments', 'CommentController@all')->hasPermission('read_comment');
    Route::get('comments/{comment}', 'CommentController@show')->hasPermission('read_comment');
    Route::put('comments/{comment}', 'CommentController@update')->hasPermission('modify_comment');
    Route::delete('comments/{comment}', 'CommentController@destroy')->hasPermission('delete_comment');
    Route::name('comments.answer')->post('comments/{comment}/answer', 'CommentController@answer');
});

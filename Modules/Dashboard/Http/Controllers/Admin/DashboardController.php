<?php

namespace Modules\Dashboard\Http\Controllers\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Comment\Entities\Comment;
use Modules\Contact\Entities\Contact;
use Modules\Home\Entities\SiteView;
use Modules\Order\Entities\Order;
use Modules\ProductComment\Entities\ProductComment;
use Shetabit\Shopit\Modules\Dashboard\Http\Controllers\Admin\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    public function index()
    {
        $order =  Order::query()->select('id', 'status')
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_WAIT_FOR_PAYMENT, Order::STATUS_FAILED]);
        $salesAmountByToday = $this->reportService
            ->salesAmountByDate(Carbon::now()->startOfDay(), Carbon::now()->endOfDay());
        $salesAmountByMonth = $this->reportService
            ->salesAmountByDate(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $ordersCount = $order->count();
        $todayOrdersCount = $order->whereBetween('created_at',
            [Carbon::createFromTimestamp(verta()->startDay()->getTimestamp()),
                Carbon::now()
            ])
            ->count();
        $lastOrders = Order::withCommonRelations()->latest('id')->take(7)->get()->append('total_amount');
        $contacts = Contact::query()->take(5)->latest('id')->get();
        $productComments = ProductComment::query()->latest('id')->with(['creator', 'product'])
            ->take(6)->get();
        $newProductCommentsCount = ProductComment::query()->latest('id')
            ->whereStatus(ProductComment::STATUS_PENDING)->count();
        $blogComments = Comment::query()->with('commentable')->take(6)->get();
        $newBlogCommentsCount = Comment::query()
            ->whereStatus(Comment::STATUS_PENDING)->count();
        //start site views
        $siteviews = SiteView::query()
            ->orderBy('id','DESC')
            ->where('date', '>=', now()->subDays(30)->endOfDay()
            )->get()->groupBy('date');

        //calculate siteview
        $siteviewslist = array();

        foreach ($siteviews as $y => $siteview) {
            $siteviewslist[$y] = 0;
            foreach ($siteview as $x) {
                $siteviewslist[$y] = $siteviewslist[$y] + $x->count;
            }
        }
        //end site views

        $data = [
            'orders_count' => $ordersCount,
            'today_orders_count' => $todayOrdersCount,
            'sales_amount_by_today' => $salesAmountByToday,
            'sales_amount_by_month' => $salesAmountByMonth,
            'year_statistics' => $this->reportService->getByYear(0), // باید حذف شود
            'gender_statistics' => $this->reportService->getCustomersGender(),
            'logs' => $this->reportService->getLogs(),
            'orders_by_status' => $this->reportService->getByWeek('ordersStatusByDate'),
            'last_logins' => $this->reportService->getLastLogins(),
            'last_orders' => $lastOrders,
            'contacts' => $contacts,
            'site_views' => $siteviewslist,
            'comments' => [
                'product_comments' => $productComments,
                'new_product_comments_count' => $newProductCommentsCount,
                'blog_comments' => $blogComments,
                'new_blog_comments_count' => $newBlogCommentsCount
            ]
        ];

        return response()->success('', $data);
    }

    public function siteViews()
    {
        $siteviews = Cache::remember('site-views', 60*30, function () {
            return SiteView::query()
                ->orderBy('id','DESC')
                ->where('date', '>=', now()->subDays(30)->endOfDay()
                )->get()->groupBy('date');
        });

        $siteviewslist = array();

        foreach ($siteviews as $y => $siteview) {
            $siteviewslist[$y] = 0;
            foreach ($siteview as $x) {
                $siteviewslist[$y] = $siteviewslist[$y] + $x->count;
            }
        }

        $data = [
            'site_views' => $siteviewslist,
        ];

        return response()->success('site views',$data);
    }

    public function comments()
    {
        $productComments = Cache::rememberForever('product-comments', function () {
            return ProductComment::query()->latest('id')->with(['creator', 'product'])
                ->take(6)->get();
        });

        $newProductCommentsCount = Cache::rememberForever('new-product-comments-count', function () {
            return ProductComment::query()->latest('id')
                ->whereStatus(ProductComment::STATUS_PENDING)->count();
        });

        $blogComments = Cache::rememberForever('blog-comments', function () {
            return Comment::query()->with('commentable')->take(6)->get();
        });

        $newBlogCommentsCount = Cache::rememberForever('new-blog-comments-count', function () {
            return Comment::query()->whereStatus(Comment::STATUS_PENDING)->count();
        });

        $data = [
            'comments' => [
                'product_comments' => $productComments,
                'new_product_comments_count' => $newProductCommentsCount,
                'blog_comments' => $blogComments,
                'new_blog_comments_count' => $newBlogCommentsCount
            ]
        ];

        return response()->success('', $data);
    }

    public function lastOrders()
    {
        $lastOrders = Cache::rememberForever('last-orders', function () {
            return Order::withCommonRelations()->latest('id')->take(7)->get()
                ->append('total_amount');
        });

        $data = [
            'last_orders' => $lastOrders,
        ];

        return response()->success('', $data);
    }

    public function contacts()
    {
        $contacts = Cache::rememberForever('contacts', function () {
            return Contact::query()->take(5)->latest('id')->get();
        });

        $data = [
            'contacts' => $contacts,
        ];

        return response()->success('', $data);
    }

    public function lastLogins()
    {
        $data = [
            'last_logins' => $this->reportService->getLastLogins(),
        ];

        return response()->success('', $data);
    }

    public function ordersCount()
    {
        $order =  Order::query()->select('id', 'status')
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_WAIT_FOR_PAYMENT, Order::STATUS_FAILED]);

        $ordersCount = $order->count();

        $data = [
            'orders_count' => $ordersCount,
        ];

        return response()->success('', $data);
    }

    public function todayOrdersCount()
    {
        $order =  Order::query()->select('id', 'status')
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_WAIT_FOR_PAYMENT, Order::STATUS_FAILED]);

        $todayOrdersCount = $order->whereBetween('created_at',
            [Carbon::createFromTimestamp(verta()->startDay()->getTimestamp()),
                Carbon::now()
            ])
            ->count();

        $data = [
            'today_orders_count' => $todayOrdersCount,
        ];

        return response()->success('', $data);
    }

    public function salesAmountByToday()
    {
        $salesAmountByToday = $this->reportService
            ->salesAmountByDate(Carbon::now()->startOfDay(), Carbon::now()->endOfDay());

        $data = [
            'sales_amount_by_today' => $salesAmountByToday,
        ];

        return response()->success('', $data);
    }

    public function salesAmountByMonth()
    {
        $salesAmountByMonth = $this->reportService
            ->salesAmountByDate(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $data = [
            'sales_amount_by_month' => $salesAmountByMonth
        ];

        return response()->success('', $data);
    }

    public function genderStatistics()
    {
        $data = [
            'gender_statistics' => $this->reportService->getCustomersGender(),
        ];

        return response()->success('', $data);
    }

    public function logs()
    {
        $data = [
            'logs' => $this->reportService->getLogs(),
        ];

        return response()->success('', $data);
    }

    public function orderByStatus()
    {
        $data = [
            'orders_by_status' => $this->reportService->getByWeek('ordersStatusByDate'),
        ];

        return response()->success('', $data);
    }

    public function webIndex(){
        return view('dashboard::admin.dashboard');
    }
}

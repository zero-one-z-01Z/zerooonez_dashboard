<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bill;
use App\Models\Highlight;
use App\Models\Scanner;
use App\Models\ScannerCompany;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * يجمع أعداد الموارد والمبيعات ومقارنات الأشهر وحالات المزادات ورسوم السنوات لعرض الصفحة الرئيسية.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function index(){
        $numbers = [];

        $user = User::query();
        $numbers[] = $this->prepare_data($user,'created_at',__('admin.users'),route('admin.users.index'),'primary','users');
        $companies = ScannerCompany::query();
        $numbers[] = $this->prepare_data($companies,'created_at',__('admin.companies'),route('admin.companies.index'),'success','home');
        $scanners = Scanner::query();
        $numbers[] = $this->prepare_data($scanners,'created_at',__('admin.scanners'),route('admin.scanners.index'),'light','motorbike');
        $auctions = Auction::where('status','!=','draft');
        $numbers[] = $this->prepare_data($auctions,'created_at',__('admin.auctions'),route('admin.auctions.index'),'warning','border-all');
        $tickets = Ticket::whereNotIn('status',['closed','completed']);
        $numbers[] = $this->prepare_data($tickets,'created_at',__('admin.tickets'),route('admin.tickets.index'),'secondary','ticket');
        $highlights = Highlight::query();
        $numbers[] = $this->prepare_data($highlights,'created_at',__('admin.highlights'),route('admin.highlights.index'),'info','highlight');

        // avg
        $month_data = Auction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status',['completed','out_of_platform'])->get();
        $steps = $this->dailyCountsArray($month_data);
        $count = $month_data->count();
        $avg = ["title"=>__('admin.avg_order_per_day'),'value'=>$count,'steps'=>$steps];
        $bills = Bill::where('status','paid')->get();
        // sales
        $total = 0;
        $users_profit = 0;
        $app_profit = 0;
        foreach ($bills as $order){
            $price = $order->total;
            $auction_price = $order->auction?->price;
            $total += $price+$auction_price;
            $app_profit +=  $price;
            $users_profit += $auction_price - $price;
        }
        $users_percentage = 0;
        $app_percentage = 0;
        if($total){
            $users_percentage = round($users_profit/$total*100,2);
            $app_percentage = round($app_profit/$total*100,2);
        }
        $total = number_format($total, 2);
        [$val,$class] = $this->getOrderSales('created_at', 'offer.price');
        $sales = ["title"=>__('admin.total_sales_this_month'),'value'=>$total,
            'left_title'=>__('admin.auctions'),'right_title'=>env('APP_NAME'),
            'growth'=>$val,'class'=>$class,'left_value'=>$users_profit,'right_value'=>$app_profit,
            'left_percentage'=>$users_percentage,'right_percentage'=>$app_percentage];

        // total earn
        $total_earn = null;
        //        $cities = City::all();
        //        foreach ($cities as $city) {
        //            $cityOrders = Auction::whereHas('address', function ($q) use ($city) {
        //                $q->whereHas('area', function ($a) use ($city) {
        //                    $a->where('city_id', $city->id);
        //                });
        //            });
        //            $count = (clone $cityOrders)->count();
        //            $percentage = $this->getChangeGrowth((clone $cityOrders), 'created_at'); // returns something like "12.5%"
        //            $total_earn[] = [
        //                "title" => $city->name,
        //                "percentage" => $percentage,
        //                "class" => $percentage > 0 ? 'success' : ($percentage == 0 ? 'info' : 'danger'),
        //                "description" => __('admin.total_auctions') . ' ' . $count
        //            ];
        //        }
        //        if(count($total_earn)>8){
        //            $sorted = collect($total_earn)->sortByDesc('percentage')->values();
        //            $top = $sorted->take(4);
        //            $bottom = $sorted->sortBy('percentage')->take(4)->values();
        //            $total_earn = $top->merge($bottom);
        //        }


        $circle_chart = $this->getCircleChartPrepare();

        $list_compare[] = $this->compare_month('completed');
        $list_compare[] = $this->compare_month('owner_cancelled',true);
        $list_compare[] = $this->compare_month('scanner_cancelled',true);
        $list_compare[] = $this->compare_month('admin_cancelled',true);

        // line chart
        $total_orders = Auction::whereYear('created_at', now()->year)->count();
        $currentYear = date('Y');
        $previousYear = date('Y', strtotime('-1 year'));

        $this_year_counts = $this->byYear($currentYear);
        $previous_Year_counts = $this->byYear($previousYear);

        // progress
        $pending = Auction::where('status','accepted')->count();
        $in_progress = Auction::where('status','in_progress')->count();
        $ready_to_publish = Auction::where('status','ready_to_publish')->count();
        $inShow = Auction::where('status','in_show')->count();
        $currentOrders = $pending+$in_progress+$inShow;
        if($currentOrders == 0){
            $pendingPercentage = 0;
            $ready_to_publishPercentage = 0;
            $progressPercentage = 0;
            $inShowPercentage = 0;
        }else{
            $pendingPercentage = round($pending/$currentOrders*100,2);
            $progressPercentage = round($in_progress/$currentOrders*100,2);
            $ready_to_publishPercentage = round($ready_to_publish/$currentOrders*100,2);
            $inShowPercentage = round($inShow/$currentOrders*100,2);

        }
        $progress[] = ['icon'=>"circle-arrow-up",'title'=>__('admin.new_auctions'),'value'=>$pendingPercentage,'class'=>'success','description'=>$pending];
        $progress[] = ['icon'=>"hourglass",'title'=>__('admin.in_progress_auctions'),'value'=>$progressPercentage,'class'=>'info','description'=>$in_progress];
        $progress[] = ['icon'=>"hourglass",'title'=>__('admin.ready_to_publish_auctions'),'value'=>$ready_to_publishPercentage,'class'=>'info','description'=>$ready_to_publish];
        $progress[] = ['icon'=>"ad",'title'=>__('admin.in_show_auction'),'value'=>$inShowPercentage,'class'=>'success','description'=>$inShow];

        return view('zerooonez-dashboard::screen.home',
            compact('numbers','avg','sales',
                'total_earn','circle_chart','list_compare','total_orders','this_year_counts','previous_Year_counts',
            'progress'));
    }

    /**
     * يجهز بطاقة حالة مزاد محددة تشمل العدد الكلي ونمو الشهر مع خيار عكس إشارة النمو.
     *
     * @param string $status حالة المزاد المطلوب عدها ومقارنة نموها.
     * @param bool $reverse عكس إشارة النسبة عندما يكون انخفاض العدد نتيجة مرغوبة.
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function compare_month($status,$reverse = false)
    {
        $auctions = Auction::where('status',$status);
        $count = (clone $auctions)->count();
        $percentage = $this->getChangeGrowth((clone $auctions), 'created_at',$reverse);
        return [
            "title" => __('admin.'.$status),
            "percentage" => $percentage,
            "icon_class" => ($status=='completed'||$status=='out_of_platform')?"success":"danger",
            "class" => $percentage > 0 ? 'success' : ($percentage == 0 ? 'info' : 'danger'),
            "description" => __('admin.total_auctions') . ' ' . $count
        ];
    }


    /**
     * يحول سجلات الشهر إلى مصفوفة أعداد يومية تبدأ بأصفار حتى اليوم الحالي اعتمادا على created_at الخام.
     *
     * @param \Illuminate\Support\Collection $month_data سجلات الشهر التي تحمل created_at الخام. لا يستخدم مباشرة في جسم الدالة الحالي.
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function dailyCountsArray($month_data)
    {
        $today = now();
        $dayInMonth = $today->day;

        // Initialize array with 0s



        // Step 2: Initialize array with 0s
        $auctionsPerDay = array_fill(0, $dayInMonth, 0);

        // Step 3: Loop through auctions and count per day
        foreach ($month_data as $order) {
            $day = Carbon::parse($order->getRawOriginal('created_at'))->day; // get day of month (1-31)
            $auctionsPerDay[$day - 1]++;      // subtract 1 to match 0-based array index
        }
        return array_values($auctionsPerDay);
    }

    /**
     * ينفذ عد الاستعلام ويحسب نموه الشهري ثم يعيد بيانات بطاقة إحصائية بعنوان ورابط ولون وأيقونة.
     *
     * @param \Illuminate\Database\Eloquent\Builder $model الاستعلام الأساسي الذي تحسب منه الأعداد؛ تنسخ شروطه للمقارنة.
     * @param string $key اسم عمود التاريخ المستخدم في حدود الشهر.
     * @param string $title عنوان البطاقة المترجم.
     * @param string $link رابط انتقال البطاقة إلى صفحة المورد.
     * @param string $class اسم لون البطاقة في القالب.
     * @param string $icon اسم الأيقونة في القالب.
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function prepare_data($model,$key,$title,$link,$class,$icon)
    {
        $count = $model->count();
        $growth = $this->getChangeGrowth($model,$key);
        return ['title'=>$title,'value'=>$count,'link'=>$link,
            'class'=>$class,'icon'=>$icon,'description'=>__('admin.than_last_month'),'growth'=>$growth.'%'];
    }

    /**
     * يقارن عدد نتائج الاستعلام في الشهر الحالي والسابق ويعيد نسبة التغير المقربة مع خيار عكس الإشارة.
     *
     * @param \Illuminate\Database\Eloquent\Builder $model الاستعلام الأساسي الذي تحسب منه الأعداد؛ تنسخ شروطه للمقارنة.
     * @param string $key اسم عمود التاريخ المستخدم في حدود الشهر.
     * @param bool $reverse عكس إشارة النسبة عندما يكون انخفاض العدد نتيجة مرغوبة.
     * @return float نسبة نمو عدد النتائج بين الشهرين.
     */
    public function getChangeGrowth($model,$key,$reverse = false)
    {

        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Count new users
        $thisMonth = (clone $model)->whereBetween($key, [$startOfThisMonth, $endOfThisMonth])->count();
        $lastMonth = (clone $model)->whereBetween($key, [$startOfLastMonth, $endOfLastMonth])->count();

        // Calculate percentage change
        if ($lastMonth === 0) {
            $percentageChange = $thisMonth > 0 ? 100 : 0;
        } else {
            $percentageChange = (($thisMonth - $lastMonth) / $lastMonth) * 100;
        }
        if($reverse){
            $percentageChange = $percentageChange*-1;
        }
        // Optional: round to 1 decimal place
        $percentageChange = round($percentageChange, 1);

        return $percentageChange;

    }

    /**
     * يقارن مجموع الحقل المطلوب للمزادات المكتملة وخارج المنصة بين الشهرين، ويعيد النسبة ولون المؤشر.
     *
     * @param string $key اسم عمود التاريخ المستخدم في حدود الشهر.
     * @param string $value مسار الحقل المالي المطلوب جمعه، مثل offer.price.
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function getOrderSales($key,$value)
    {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();



        // Count new users
        $lastMonthOrders = Auction::whereIn('status',['completed','out_of_platform'])->whereBetween($key, [$startOfLastMonth, $endOfLastMonth])->get();
        $thisMonthOrders = Auction::whereIn('status',['completed','out_of_platform'])->where($key, '>=', $startOfThisMonth)->get();
        $lastMonthAvg = $lastMonthOrders->pluck($value)->filter()->sum();
        $thisMonthAvg = $thisMonthOrders->pluck($value)->filter()->sum();
        // Calculate percentage change
        if ($lastMonthAvg === 0 || is_null($lastMonthAvg)) {
            $percentageChange = $thisMonthAvg > 0 ? 100 : 0;
        } else {
            $percentageChange = (($thisMonthAvg - $lastMonthAvg) / $lastMonthAvg) * 100;
        }


        // Optional: round to 1 decimal place
        $percentageChange = round($percentageChange, 1);

        return ['' . $percentageChange . '%',$thisMonthAvg>=$lastMonthAvg?"success":"danger"];

    }

    /**
     * يحسب توزيع مزادات الشهر على الجديدة والجارية والمكتملة والملغاة ويعيد تسميات الرسم وقيمه وإجماليه.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function getCircleChartPrepare()
    {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $auctions = Auction::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth]);
        $data['labels'] = [__('admin.new_auctions'),__('admin.in_progress_auctions'),__('admin.completed'),
            __('admin.cancelled')];
        $inProgressOrders = (clone $auctions)->whereIn('status', ['accepted','in_progress','in_show','ready_to_publish'])->count();
        $completedOrders = (clone $auctions)->whereIn('status', ['completed','out_of_platform'])->count();
        $newOrders = (clone $auctions)->where('status', 'pending')->count();
        $cancelledOrders = (clone $auctions)->whereIn('status', ['owner_cancelled','scanner_cancelled','admin_cancelled',
            'admin_cancelled'])->count();
        $data['series'] = [$newOrders,$inProgressOrders,$completedOrders,$cancelledOrders];
        $total_counts = $newOrders+$inProgressOrders+$completedOrders+$cancelledOrders;
        $data['title'] = $total_counts;
        $data['description'] = __('admin.total_auctions');
        return $data;
    }

    /**
     * ينفذ عددا لكل شهر من السنة المحددة مع استبعاد المسودات ويرجع اثنتي عشرة قيمة مرتبة.
     *
     * @param int|string $year السنة التي تحسب شهورها الاثنا عشر.
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function byYear($year)
    {
        // Initialize an array to store the counts for each month
        $orderCounts = [];

        // Loop through each month of the year
        for ($month = 1; $month <= 12; $month++) {
            // Get the count of auctions for the current month and year
            $orderCount = Auction::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)->where('status','!=','draft')
                ->count();

            // Store the count in the array
            $orderCounts[] = $orderCount;
        }

        return $orderCounts;
    }

    /**
     * يحفظ اللغة في الجلسة ويغير لغة التطبيق ثم يحول إلى الصفحة المخصصة لدور ملخص الداشبورد.
     *
     * @param string $lang رمز اللغة المحفوظ في الجلسة والمستخدم في الترجمة.
     * @return \Illuminate\Http\RedirectResponse التحويل ورسالة الحالة عند وجودها.
     */
    public function change_lang($lang){
        session(['lang' => $lang]);
        app()->setLocale($lang);
        return redirect()->route('admin.home');
    }
}

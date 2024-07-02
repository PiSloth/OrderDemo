<?php


namespace App\Livewire\Orders;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\Quality;
use App\Models\Status;
use Asantibanez\LivewireCharts\Facades\LivewireCharts;
use Asantibanez\LivewireCharts\LivewireCharts as LivewireChartsLivewireCharts;
use Asantibanez\LivewireCharts\Models\RadarChartModel;
use Asantibanez\LivewireCharts\Models\TreeMapChartModel;
use Livewire\Attributes\Title;
use Livewire\Component;
use WireUi\Traits\Actions;
use Carbon\Carbon;

class Report extends Component
{
    use Actions;
    #[Title('Report')]

    public $types = ['Gold', '18K', 'Diamond'];
    public $category = [1, 2, 3];

    public $colors = [
        'Gold' => '#f6ad55',
        '18K' => '#fc8181',
        'Diamond' => '#000000',
    ];
    public $viewBy;
    protected $columnChartTitle;
    public $color;
    public $firstRun = true;
    public $showDataLabels = true;
    public $forCategoryQuality;
    public $columnTitle;
    public $start_date;
    public $end_date;


    public function boot()
    {
        if (!$this->start_date) {
            $this->start_date = "2023-11-10";
        }
        if (!$this->end_date) {
            $this->end_date = Carbon::today();
        }
    }

    protected $listeners = [
        'onPointClick' => 'handleOnPointClick',
        'onSliceClick' => 'handleOnSliceClick',
        'onColumnClick' => 'handleOnColumnClick',
        'onBlockClick' => 'handleOnBlockClick',
    ];


    public function toggle($toggleName, $choose)
    {
        $this->$toggleName = $choose;
        $title = "View by $choose";
        $this->columnTitle =  $title;
        // dd($this->columnTitle);
    }

    public function handleOnPointClick($point)
    {
        dd($point);
    }
    public function handleOnSliceClick($slice)
    {
        dd($slice);
    }
    public function handleOnColumnClick($column)
    {
        dd($column);
    }
    public function handleOnBlockClick($block)
    {
        dd($block);
    }

    public function render()
    {
        if (!$this->columnTitle) {
            $this->columnTitle = "View by weight (g)";
        }
        //column chart title set
        if ($this->viewBy == null) {
            $this->columnChartTitle = "View by Weight in Gram";
        } else {
            $this->columnChartTitle = "View by Quantity";
        }
        //retrieve all design ids with pluck and built to an array
        $orders = Order::with('design:id,name')
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();

        $design = Design::all()->pluck('id')->toArray();
        $designOrders = Order::whereIn('design_id', $design)
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();
        $designColumnChartModel = $designOrders->groupBy('design_id')
            ->reduce(
                function ($designColumnChartModel, $data) {
                    $design = $data->first()->design->name;
                    $value = $data->sum($this->viewBy ?? 'weight');
                    return $designColumnChartModel->addColumn($design, $value, $this->color);
                },
                LivewireCharts::columnChartModel()
                    ->setTitle($this->columnChartTitle)
                    ->setAnimated($this->firstRun)
                    ->withOnColumnClickEventName('onColumnClick')
                    ->setLegendVisibility(true)
                    ->setDataLabelsEnabled(true)
                    // ->stacked()
                    // ->setOpacity(0.5)
                    // ->multiColumn()
                    ->setColors(['#eee000', '#fc8181', '#e30384', '#000000'])
                    ->setColumnWidth(30)
                    ->withGrid()
                // ->setJsonConfig([
                //     // 'plotOptions.bar.distributed' => true,
                // ]),
            );

        //    End design chart


        // Pie Chart by Category
        $category = Category::all()->pluck('id')->toArray();
        $categoryOrders = Order::whereIn('category_id', $category)
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();

        $categoryPieChartModel = $categoryOrders->groupBy('category_id')
            ->reduce(
                function ($categoryPieChartModel, $data) {
                    $type = $data->first()->category->name;
                    $value = $data->sum($this->forCategoryQuality ?? 'weight');

                    return $categoryPieChartModel->addSlice($type, $value, $this->color);
                },
                LivewireCharts::pieChartModel()
                    ->setTitle($this->columnTitle)
                    ->setAnimated($this->firstRun)
                    ->setType('donut')
                    ->setOpacity(0.75)
                    ->withOnSliceClickEvent('onSliceClick')
                    ->withLegend()
                    ->legendPositionBottom()
                    ->legendHorizontallyAlignedCenter()
                    ->setDataLabelsEnabled($this->showDataLabels)
                    ->setColors(['#224193', '#DF3C5F',  '#6F9BD1', '#f66665'])
                    ->setJsonConfig([
                        'dropShadows.enabled' => true,
                        'dropShadows.top' => 0,
                        'dropShadows.left' => 5,
                        // 'dropShadows.color' => '#000000',
                        'dropShadows.opacity' => 0.5,

                    ]),
                //     'plotOptions.pie.endAngle' => 90,
                //     'plotOptions.pie.offsetY' => 10,
                //     'grid.padding.bottom' => -180,
                // ])
            );

        // End Pie Chart by Category
        $lineQuality = Quality::all()->pluck('id')->toArray();
        $lineOrders = $orders->whereIn('quality_id', $lineQuality)
            ->whereBetween('created_at', [$this->start_date, $this->end_date]);
        $qualineChartModel = $lineOrders->groupBy('quality_id')
            ->reduce(
                function ($qualineChartModel, $data) use ($lineOrders) {
                    $title = $data->first()->quality->name;
                    $value = $data->sum($this->forCategoryQuality ?? 'weight');
                    $value2 = $value + 1;
                    return $qualineChartModel->addPoint($title, $value);
                    // ->addMarker($title, $value2);
                },
                LivewireCharts::lineChartModel()
                    ->setTitle($this->columnTitle)
                    ->setAnimated($this->firstRun)
                    ->withOnPointClickEvent('onPointClick')
                    ->setSmoothCurve()
                    ->setXAxisVisible(true)
                    ->setDataLabelsEnabled($this->showDataLabels)
                    ->withGrid()
            );
        // End Quality line model

        $status = Status::all()->pluck('id')->toArray();
        $categoryName = Category::all();
        $statusOrders = Order::whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();
        $statusRadarChartModel = $statusOrders->groupBy('category_id')
            ->reduce(
                function (RadarChartModel $statusRadarChartModel, $data) use ($categoryName) {
                    $series = $data->first()->category->name;
                    $title = $data->first()->status->name;
                    $value = $data->sum('weight');
                    return $statusRadarChartModel
                        // ->addSeries($series, $title, $value, ["A", "B"])
                        ->addSeries($series, "", $value)
                        ->addSeries("", $title, $value);
                },
                LivewireCharts::radarChartModel()
                    ->setAnimated($this->firstRun)
            );
        // @dd($statusRadarChartModel);



        $areaChartModel = $orders
            ->reduce(
                function ($areaChartModel, $data) use ($orders) {
                    $index = $orders->search($data);
                    return $areaChartModel->addPoint($index, $data->qty, ['id' => $data->id]);
                },
                LivewireCharts::areaChartModel()
                    ->setAnimated($this->firstRun)
                    ->setColor('#f6ad55')
                    ->withOnPointClickEvent('onAreaPointClick')
                    ->setDataLabelsEnabled($this->showDataLabels)
                    ->setXAxisVisible(true)
                    ->sparklined()
            );


        $statuses = Status::all();

        $columnChartModel = $designOrders->groupBy('design_id')
            ->reduce(
                function ($columnChartModel, $data) use ($statuses) {
                    $design = $data->first()->design->name;
                    $valueQty = $data->sum('qty');
                    $value = $data->sum('weight');

                    return $columnChartModel
                        ->addSeriesColumn("Sum Of Qty", $design, $valueQty)
                        ->addSeriesColumn("Sum of Weight", $design, $value);
                },
                LivewireCharts::multiColumnChartModel()
                    ->setColors(['#eee000', '#d41bff', '#ecaa3b', '#f66665'])
                    ->multiColumn()
                    ->stacked()
                    ->setColumnWidth(30)
                    ->withGrid()
                    ->setDataLabelsEnabled(true)

            );

        $category = Category::all()->pluck('id')->toArray();

        $multiLineChartModel = $orders->groupBy('category_id')
            ->reduce(
                function ($multiLineChartModel, $data) use ($category, $orders) {
                    $index = $orders->search($data);
                    $type = $data->first()->category->name;
                    // dd($index);

                    return $multiLineChartModel
                        ->addSeriesPoint($type, "Weight", $data->sum('weight'))
                        ->addSeriesPoint($type, "Gram",  $data->sum('qty'));
                    // ->addPoint($index, $data->qty);
                },
                LivewireCharts::multiLineChartModel()
                    //->setTitle('Expenses by Type')

                    ->setAnimated($this->firstRun)
                    ->withOnPointClickEvent('onPointClick')
                    ->setSmoothCurve()
                    ->multiLine()
                    ->setDataLabelsEnabled($this->showDataLabels)
                    ->sparklined()
                // ->setColors(['#b01a1b', '#d41b2c', '#ec3c3b', '#f66665'])
            );

        $multiColumnChartModel = $orders->groupBy('category_id')
            ->reduce(
                function ($multiColumnChartModel, $data) {
                    $type = $data->first()->category->name;

                    return $multiColumnChartModel
                        ->addSeriesColumn($type, 3, $data->sum('qty'));
                },
                LivewireCharts::multiColumnChartModel()
                    ->setAnimated($this->firstRun)
                    ->setDataLabelsEnabled($this->showDataLabels)
                    ->withOnColumnClickEventName('onColumnClick')
                    ->setTitle('Revenue per Year (K)')
                    ->stacked()
                    ->withGrid()
            );



        // $treeChartModel = $orders->groupBy('status_id')
        //     ->reduce(
        //         function (TreeMapChartModel $chartModel, $data) {
        //             $type = $data->first()->category->name;
        //             $value = $data->sum('qty');

        //             return $chartModel->addBlock($type, $value)->addColor($this->colors[$type]);
        //         },
        //         LivewireCharts::treeMapChartModel()
        //             ->setTitle('Expenses Weighttt')
        //             ->setAnimated($this->firstRun)
        //             ->setDistributed(true)
        //             ->withOnBlockClickEvent('onBlockClick')
        //     );

        $this->firstRun = false;

        return view('livewire.orders.report', [
            'designColumnChartModel' => $designColumnChartModel,
            'categoryPieChartModel' => $categoryPieChartModel,
            'qualineChartModel' => $qualineChartModel,
            'columnChartModel' => $columnChartModel,
            'areaChartModel' => $areaChartModel,
            'multiLineChartModel' => $multiLineChartModel,
            'multiColumnChartModel' => $multiColumnChartModel,
            'radarChartModel' => $statusRadarChartModel,
            // 'treeChartModel' => $treeChartModel,

        ]);
    }
}

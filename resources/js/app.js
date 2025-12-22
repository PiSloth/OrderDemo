import "./bootstrap";
import "./add.order";
import "flowbite";
import "./initFixer";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index";
import "flatpickr/dist/plugins/monthSelect/style.css";

window.flatpickr = flatpickr;
window.monthSelectPlugin = monthSelectPlugin;

import ApexCharts from "apexcharts";
window.ApexCharts = ApexCharts;

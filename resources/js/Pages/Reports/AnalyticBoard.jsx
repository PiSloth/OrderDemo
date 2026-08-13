import React from 'react';
import { Head } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import ReportImageboardView from '../../components/ReportStudio/ReportImageboardView';

export default function AnalyticBoard({ taxonomies, todoOptions }) {
  return (
    <AsideLayout title="Analytic Report">
      <Head title="Analytic Report" />
      <div className="py-4">
        <ReportImageboardView taxonomies={taxonomies} todoOptions={todoOptions} />
      </div>
    </AsideLayout>
  );
}

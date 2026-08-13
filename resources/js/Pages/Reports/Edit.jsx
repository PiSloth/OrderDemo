import React from 'react';
import { Head } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import ReportEditorContainer from '../../Components/ReportStudio/ReportEditorContainer';

export default function Edit({ report, taxonomies, todoOptions }) {
  return (
    <AsideLayout title={`Edit Report: ${report?.title || ''}`}>
      <Head title="Edit Report" />
      <div className="py-4">
        <ReportEditorContainer report={report} taxonomies={taxonomies} todoOptions={todoOptions} />
      </div>
    </AsideLayout>
  );
}

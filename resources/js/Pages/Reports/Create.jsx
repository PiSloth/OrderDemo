import React from 'react';
import { Head } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import ReportEditorContainer from '../../Components/ReportStudio/ReportEditorContainer';

export default function Create({ taxonomies, todoOptions }) {
  return (
    <AsideLayout title="New Rich Text Report">
      <Head title="New Rich Text Report Studio" />
      <div className="py-4">
        <ReportEditorContainer taxonomies={taxonomies} todoOptions={todoOptions} />
      </div>
    </AsideLayout>
  );
}

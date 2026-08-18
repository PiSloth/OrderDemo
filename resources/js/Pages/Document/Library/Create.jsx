import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import ReactRichTextEditor from '../../../components/Document/ReactRichTextEditor';

import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  TextField,
  MenuItem,
  Stack,
  Alert,
  CircularProgress,
  Divider,
  Grid
} from '@mui/material';

import SaveIcon from '@mui/icons-material/Save';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';

export default function Create({ departments = [], documentTypes = [] }) {
  const [createNewType, setCreateNewType] = useState(false);

  const { data, setData, post, processing, errors } = useForm({
    title: '',
    company_document_type_id: '',
    new_document_type: '',
    department_id: '',
    announced_at: '',
    body: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post('/document/library');
  };

  return (
    <AsideLayout title="Create Document">
      <Head title="Create Document - Document Library" />

      <Box className="max-w-5xl mx-auto space-y-6">
        {/* Top Header */}
        <Box className="flex items-center justify-between">
          <Box>
            <Typography variant="h5" className="font-extrabold text-slate-900 dark:text-slate-100">
              Create New Document
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Publish policies, SOPs, workflows, and announcements with rich formatting.
            </Typography>
          </Box>

          <Button
            variant="outlined"
            startIcon={<ArrowBackIcon />}
            component={Link}
            href="/document/library"
            sx={{ textTransform: 'none', borderRadius: 2 }}
          >
            Back to Library
          </Button>
        </Box>

        {Object.keys(errors).length > 0 && (
          <Alert severity="error" className="rounded-xl">
            Please correct the validation errors below before submitting.
          </Alert>
        )}

        {/* Main Form */}
        <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
          <CardContent className="p-6">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Title */}
                <div className="md:col-span-2">
                  <TextField
                    label="Document Title"
                    required
                    fullWidth
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    error={Boolean(errors.title)}
                    helperText={errors.title}
                    placeholder="e.g. Standard Operating Procedure for Daily Cash Settlement"
                  />
                </div>

                {/* Document Type */}
                <div>
                  {!createNewType ? (
                    <TextField
                      select
                      fullWidth
                      label="Document Type / Category"
                      value={data.company_document_type_id}
                      onChange={(e) => setData('company_document_type_id', e.target.value)}
                      error={Boolean(errors.company_document_type_id)}
                      helperText={errors.company_document_type_id}
                    >
                      <MenuItem value="">-- Select Type --</MenuItem>
                      {documentTypes.map((type) => (
                        <MenuItem key={type.id} value={type.id}>
                          {type.name}
                        </MenuItem>
                      ))}
                    </TextField>
                  ) : (
                    <TextField
                      fullWidth
                      label="New Document Type Name"
                      value={data.new_document_type}
                      onChange={(e) => setData('new_document_type', e.target.value)}
                      error={Boolean(errors.new_document_type)}
                      helperText={errors.new_document_type || 'e.g. Policy, Checklist, Guideline'}
                      placeholder="e.g. Policy, Guideline"
                    />
                  )}

                  <Box className="mt-1">
                    <Button
                      size="small"
                      variant="text"
                      onClick={() => {
                        setCreateNewType(!createNewType);
                        if (!createNewType) {
                          setData('company_document_type_id', '');
                        } else {
                          setData('new_document_type', '');
                        }
                      }}
                      sx={{ textTransform: 'none', fontSize: '0.75rem', p: 0 }}
                    >
                      {createNewType ? '← Select existing type' : '+ Create new document type'}
                    </Button>
                  </Box>
                </div>

                {/* Department */}
                <div>
                  <TextField
                    select
                    required
                    fullWidth
                    label="Department"
                    value={data.department_id}
                    onChange={(e) => setData('department_id', e.target.value)}
                    error={Boolean(errors.department_id)}
                    helperText={errors.department_id}
                  >
                    <MenuItem value="">-- Select Department --</MenuItem>
                    {departments.map((dept) => (
                      <MenuItem key={dept.id} value={dept.id}>
                        {dept.name}
                      </MenuItem>
                    ))}
                  </TextField>
                </div>

                {/* Announcement Date */}
                <div>
                  <TextField
                    type="date"
                    fullWidth
                    label="Announcement Date (Optional)"
                    InputLabelProps={{ shrink: true }}
                    value={data.announced_at}
                    onChange={(e) => setData('announced_at', e.target.value)}
                    error={Boolean(errors.announced_at)}
                    helperText={errors.announced_at || 'Set date if this document is a company announcement'}
                  />
                </div>
              </div>

              <Divider />

              {/* Rich Text Editor */}
              <div className="space-y-2">
                <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-200">
                  Document Content <span className="text-red-500">*</span>
                </Typography>
                <ReactRichTextEditor
                  value={data.body}
                  onChange={(html) => setData('body', html)}
                  placeholder="Type document content, insert images, format tables, and organize sections..."
                  minHeight="380px"
                />
                {errors.body && (
                  <Typography variant="caption" className="text-red-600 block mt-1">
                    {errors.body}
                  </Typography>
                )}
              </div>

              {/* Actions */}
              <Box className="flex items-center justify-end gap-3 pt-4">
                <Button
                  variant="outlined"
                  component={Link}
                  href="/document/library"
                  disabled={processing}
                  sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  variant="contained"
                  color="primary"
                  disabled={processing}
                  startIcon={processing ? <CircularProgress size={16} color="inherit" /> : <SaveIcon />}
                  sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2, px: 3 }}
                >
                  {processing ? 'Saving...' : 'Save & Publish Document'}
                </Button>
              </Box>
            </form>
          </CardContent>
        </Card>
      </Box>
    </AsideLayout>
  );
}

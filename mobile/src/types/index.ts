export interface UserProfile {
  id: number;
  name: string;
  email: string;
  role: string;
  department?: string;
  position?: string;
}

export interface TaskTemplateEvidenceField {
  id: number;
  field_name: string;
  field_label: string;
  field_type: 'text' | 'number' | 'photo' | 'select' | 'checkbox';
  is_required: boolean;
  options?: string[];
}

export interface TaskTemplate {
  id: number;
  title: string;
  description?: string;
  requires_images: boolean;
  image_remark_required: boolean;
  evidence_fields?: TaskTemplateEvidenceField[];
}

export interface TaskSubmissionImage {
  id: number;
  image_url: string;
  file_size_bytes?: number;
  original_name?: string;
  created_at: string;
}

export interface TaskSubmission {
  id: number;
  submitted_at: string;
  status: 'pending_approval' | 'approved' | 'rejected';
  remark?: string;
  evidence_data?: string;
  is_late: boolean;
  images?: TaskSubmissionImage[];
}

export interface TaskInstance {
  id: number;
  title: string;
  status: 'pending' | 'pending_approval' | 'approved' | 'rejected' | 'cancelled';
  due_at?: string;
  submitted_at?: string;
  required_image_count?: number;
  template?: TaskTemplate;
  latest_submission?: TaskSubmission;
}

export interface TaskNotificationItem {
  id: number;
  type: string;
  title: string;
  message: string;
  read: boolean;
  created_at: string;
  data?: Record<string, any>;
}

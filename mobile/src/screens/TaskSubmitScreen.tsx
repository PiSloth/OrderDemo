import React, { useState } from 'react';
import {
  StyleSheet,
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  Image,
  Alert,
  ActivityIndicator,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import api from '../services/api';
import { autoSizeAndCompressPhoto, CompressedImageResult } from '../services/imageCompressor';
import { TaskInstance } from '../types';

interface TaskSubmitScreenProps {
  task: TaskInstance;
  onBack: () => void;
  onSubmitSuccess: () => void;
}

export const TaskSubmitScreen: React.FC<TaskSubmitScreenProps> = ({
  task,
  onBack,
  onSubmitSuccess,
}) => {
  const [remark, setRemark] = useState('');
  const [images, setImages] = useState<CompressedImageResult[]>([]);
  const [compressing, setCompressing] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Pick or capture photo
  const handleSelectPhoto = async (useCamera: boolean) => {
    try {
      let result;
      if (useCamera) {
        const { status } = await ImagePicker.requestCameraPermissionsAsync();
        if (status !== 'granted') {
          Alert.alert('Permission Required', 'Camera permission is required to capture evidence photos.');
          return;
        }
        result = await ImagePicker.launchCameraAsync({
          allowsEditing: false,
          quality: 1, // Capture full raw quality first
        });
      } else {
        result = await ImagePicker.launchImageLibraryAsync({
          allowsEditing: false,
          quality: 1,
        });
      }

      if (!result.canceled && result.assets && result.assets.length > 0) {
        const selectedAsset = result.assets[0];
        setCompressing(true);

        // Client-side auto-sizing & compression (1920px JPEG @ 0.75)
        const compressed = await autoSizeAndCompressPhoto(selectedAsset.uri);

        setImages((prev) => [...prev, compressed]);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to process evidence photo.');
    } finally {
      setCompressing(false);
    }
  };

  const handleRemovePhoto = (index: number) => {
    setImages((prev) => prev.filter((_, i) => i !== index));
  };

  const handleSubmitTask = async () => {
    if (task.template?.requires_images && images.length < (task.required_image_count ?? 1)) {
      Alert.alert(
        'Evidence Missing',
        `This task requires at least ${task.required_image_count ?? 1} evidence photo(s).`
      );
      return;
    }

    setSubmitting(true);

    try {
      const formData = new FormData();
      formData.append('remark', remark);

      // Append compressed photos as Multipart form files
      images.forEach((img, idx) => {
        formData.append('images[]', {
          uri: img.uri,
          name: img.name || `evidence_${idx}.jpg`,
          type: img.type || 'image/jpeg',
        } as any);
      });

      await api.post(`/tasks/${task.id}/submit`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      Alert.alert('Success 🎉', 'Task submitted successfully and sent for approval!', [
        { text: 'OK', onPress: onSubmitSuccess },
      ]);
    } catch (error: any) {
      const msg = error.response?.data?.message || 'Failed to submit task. Please try again.';
      Alert.alert('Submission Error', msg);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      {/* Top Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={onBack}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>
          Submit Task
        </Text>
        <View style={{ width: 60 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        {/* Task Info Header */}
        <View style={styles.infoCard}>
          <Text style={styles.taskTitle}>{task.title}</Text>
          {task.template?.description && (
            <Text style={styles.taskDesc}>{task.template.description}</Text>
          )}

          <View style={styles.divider} />

          <View style={styles.metaRow}>
            <Text style={styles.metaLabel}>Status:</Text>
            <Text style={styles.metaValue}>{task.status.toUpperCase()}</Text>
          </View>

          <View style={styles.metaRow}>
            <Text style={styles.metaLabel}>Required Evidence Photos:</Text>
            <Text style={styles.metaValue}>{task.required_image_count ?? 1} photo(s)</Text>
          </View>
        </View>

        {/* Remark Input */}
        <View style={styles.formGroup}>
          <Text style={styles.sectionTitle}>Task Remarks & Notes</Text>
          <TextInput
            style={styles.textArea}
            placeholder="Enter execution notes, remarks, or observations..."
            placeholderTextColor="#64748B"
            value={remark}
            onChangeText={setRemark}
            multiline
            numberOfLines={4}
          />
        </View>

        {/* Photo Evidence Section */}
        <View style={styles.formGroup}>
          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionTitle}>Evidence Photos</Text>
            <Text style={styles.autoSizeTag}>{"⚡ Auto-Resized & Compressed (< 800KB)"}</Text>

          </View>

          <View style={styles.photoActionRow}>
            <TouchableOpacity
              style={styles.photoBtn}
              onPress={() => handleSelectPhoto(true)}
              disabled={compressing}
            >
              <Text style={styles.photoBtnText}>📷 Snap Photo</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.photoBtn, styles.photoBtnSecondary]}
              onPress={() => handleSelectPhoto(false)}
              disabled={compressing}
            >
              <Text style={styles.photoBtnTextSecondary}>🖼️ Gallery</Text>
            </TouchableOpacity>
          </View>

          {compressing && (
            <View style={styles.compressingNotice}>
              <ActivityIndicator size="small" color="#38BDF8" />
              <Text style={styles.compressingText}>Auto-sizing & optimizing photo resolution...</Text>
            </View>
          )}

          {/* Photo Preview Grid */}
          <View style={styles.photoGrid}>
            {images.map((img, idx) => (
              <View key={idx} style={styles.photoContainer}>
                <Image source={{ uri: img.uri }} style={styles.photoPreview} />
                <TouchableOpacity
                  style={styles.removePhotoBadge}
                  onPress={() => handleRemovePhoto(idx)}
                >
                  <Text style={styles.removePhotoText}>✕</Text>
                </TouchableOpacity>
                <Text style={styles.photoMetaText}>{img.width}x{img.height}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* Submit Action Button */}
        <TouchableOpacity
          style={[styles.submitButton, submitting && styles.submitButtonDisabled]}
          onPress={handleSubmitTask}
          disabled={submitting || compressing}
        >
          {submitting ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.submitButtonText}>Submit Task for Approval</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
    paddingTop: 50,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  backButton: {
    paddingVertical: 6,
    paddingHorizontal: 10,
    backgroundColor: '#1E293B',
    borderRadius: 8,
  },
  backText: {
    color: '#38BDF8',
    fontSize: 14,
    fontWeight: '600',
  },
  headerTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '800',
  },
  content: {
    padding: 20,
  },
  infoCard: {
    backgroundColor: '#1E293B',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: '#334155',
    marginBottom: 20,
  },
  taskTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 6,
  },
  taskDesc: {
    color: '#94A3B8',
    fontSize: 14,
    lineHeight: 20,
  },
  divider: {
    height: 1,
    backgroundColor: '#334155',
    marginVertical: 12,
  },
  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  metaLabel: {
    color: '#64748B',
    fontSize: 13,
  },
  metaValue: {
    color: '#E2E8F0',
    fontSize: 13,
    fontWeight: '600',
  },
  formGroup: {
    marginBottom: 24,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  sectionTitle: {
    color: '#F8FAFC',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 8,
  },
  autoSizeTag: {
    color: '#38BDF8',
    fontSize: 11,
    fontWeight: '600',
  },
  textArea: {
    backgroundColor: '#1E293B',
    borderWidth: 1,
    borderColor: '#334155',
    borderRadius: 12,
    padding: 14,
    color: '#F8FAFC',
    fontSize: 15,
    textAlignVertical: 'top',
    minHeight: 100,
  },
  photoActionRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 12,
  },
  photoBtn: {
    flex: 1,
    backgroundColor: '#0EA5E9',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  photoBtnSecondary: {
    backgroundColor: '#1E293B',
    borderWidth: 1,
    borderColor: '#334155',
  },
  photoBtnText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 14,
  },
  photoBtnTextSecondary: {
    color: '#CBD5E1',
    fontWeight: '700',
    fontSize: 14,
  },
  compressingNotice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
    backgroundColor: '#0F172A',
    padding: 10,
    borderRadius: 8,
  },
  compressingText: {
    color: '#38BDF8',
    fontSize: 12,
  },
  photoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  photoContainer: {
    position: 'relative',
    width: 100,
    height: 100,
    borderRadius: 10,
    overflow: 'hidden',
  },
  photoPreview: {
    width: '100%',
    height: '100%',
  },
  removePhotoBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    backgroundColor: 'rgba(239, 68, 68, 0.9)',
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  removePhotoText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  photoMetaText: {
    position: 'absolute',
    bottom: 2,
    left: 4,
    color: '#FFFFFF',
    fontSize: 9,
    backgroundColor: 'rgba(0,0,0,0.6)',
    paddingHorizontal: 4,
    borderRadius: 4,
  },
  submitButton: {
    backgroundColor: '#10B981',
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 30,
  },
  submitButtonDisabled: {
    opacity: 0.5,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
  },
});

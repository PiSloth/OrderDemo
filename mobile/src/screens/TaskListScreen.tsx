import React, { useEffect, useState } from 'react';
import {
  StyleSheet,
  View,
  Text,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import api from '../services/api';
import { TaskInstance } from '../types';

interface TaskListScreenProps {
  onSelectTask: (task: TaskInstance) => void;
  onOpenNotifications: () => void;
  onLogout: () => void;
  unreadNotificationCount: number;
}

export const TaskListScreen: React.FC<TaskListScreenProps> = ({
  onSelectTask,
  onOpenNotifications,
  onLogout,
  unreadNotificationCount,
}) => {
  const [tasks, setTasks] = useState<TaskInstance[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'submitted' | 'approved' | 'rejected'>('all');

  const fetchTasks = async (filter = statusFilter) => {
    try {
      const response = await api.get('/tasks', {
        params: { status: filter },
      });
      setTasks(response.data.data || []);
    } catch (error) {
      console.error('Error fetching submitter tasks:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchTasks();
  }, [statusFilter]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchTasks();
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'approved':
        return { label: 'Approved', bg: '#065F46', text: '#34D399' };
      case 'pending_approval':
      case 'submitted':
        return { label: 'Pending Approval', bg: '#1E3A8A', text: '#60A5FA' };
      case 'rejected':
        return { label: 'Rejected', bg: '#881337', text: '#F87171' };
      default:
        return { label: 'Action Required', bg: '#78350F', text: '#FBBF24' };
    }
  };

  const renderTaskItem = ({ item }: { item: TaskInstance }) => {
    const badge = getStatusBadge(item.status);
    const dueDateText = item.due_at ? new Date(item.due_at).toLocaleDateString() : 'No Deadline';

    return (
      <TouchableOpacity
        style={styles.taskCard}
        onPress={() => onSelectTask(item)}
        activeOpacity={0.7}
      >
        <View style={styles.cardHeader}>
          <Text style={styles.taskTitle} numberOfLines={1}>
            {item.title}
          </Text>
          <View style={[styles.badge, { backgroundColor: badge.bg }]}>
            <Text style={[styles.badgeText, { color: badge.text }]}>{badge.label}</Text>
          </View>
        </View>

        <Text style={styles.taskMeta}>
          📅 Due: <Text style={styles.taskMetaBold}>{dueDateText}</Text>
        </Text>

        {item.template && (
          <Text style={styles.taskTemplate}>
            📋 Template: {item.template.title}
          </Text>
        )}
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      {/* Navigation Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerSubtitle}>Submitter View</Text>
          <Text style={styles.headerTitle}>My Assigned Tasks</Text>
        </View>

        <View style={styles.headerActions}>
          <TouchableOpacity
            style={styles.iconButton}
            onPress={onOpenNotifications}
          >
            <Text style={styles.iconText}>🔔</Text>
            {unreadNotificationCount > 0 && (
              <View style={styles.notificationBadge}>
                <Text style={styles.notificationBadgeText}>{unreadNotificationCount}</Text>
              </View>
            )}
          </TouchableOpacity>

          <TouchableOpacity style={styles.logoutButton} onPress={onLogout}>
            <Text style={styles.logoutText}>Exit</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Status Filters */}
      <View style={styles.filterRow}>
        {(['all', 'pending', 'submitted', 'approved', 'rejected'] as const).map((filterKey) => (
          <TouchableOpacity
            key={filterKey}
            style={[
              styles.filterChip,
              statusFilter === filterKey && styles.filterChipActive,
            ]}
            onPress={() => setStatusFilter(filterKey)}
          >
            <Text
              style={[
                styles.filterChipText,
                statusFilter === filterKey && styles.filterChipTextActive,
              ]}
            >
              {filterKey.charAt(0).toUpperCase() + filterKey.slice(1)}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Task List */}
      {loading ? (
        <View style={styles.loaderContainer}>
          <ActivityIndicator size="large" color="#38BDF8" />
        </View>
      ) : (
        <FlatList
          data={tasks}
          renderItem={renderTaskItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor="#38BDF8"
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Text style={styles.emptyIcon}>📋</Text>
              <Text style={styles.emptyTitle}>No tasks found</Text>
              <Text style={styles.emptySubtitle}>There are currently no tasks matching your selected filter.</Text>
            </View>
          }
        />
      )}
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
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingBottom: 16,
  },
  headerSubtitle: {
    color: '#38BDF8',
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  headerTitle: {
    color: '#F8FAFC',
    fontSize: 22,
    fontWeight: '800',
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  iconButton: {
    backgroundColor: '#1E293B',
    padding: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#334155',
    position: 'relative',
  },
  iconText: {
    fontSize: 18,
  },
  notificationBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    backgroundColor: '#EF4444',
    borderRadius: 10,
    width: 18,
    height: 18,
    justifyContent: 'center',
    alignItems: 'center',
  },
  notificationBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '800',
  },
  logoutButton: {
    backgroundColor: '#334155',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  logoutText: {
    color: '#CBD5E1',
    fontSize: 13,
    fontWeight: '600',
  },
  filterRow: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    marginBottom: 14,
    gap: 6,
  },
  filterChip: {
    backgroundColor: '#1E293B',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#334155',
  },
  filterChipActive: {
    backgroundColor: '#0EA5E9',
    borderColor: '#38BDF8',
  },
  filterChipText: {
    color: '#94A3B8',
    fontSize: 12,
    fontWeight: '600',
  },
  filterChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  listContent: {
    paddingHorizontal: 20,
    paddingBottom: 40,
  },
  taskCard: {
    backgroundColor: '#1E293B',
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#334155',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  taskTitle: {
    color: '#F8FAFC',
    fontSize: 16,
    fontWeight: '700',
    flex: 1,
    marginRight: 8,
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  taskMeta: {
    color: '#94A3B8',
    fontSize: 13,
    marginBottom: 4,
  },
  taskMetaBold: {
    color: '#E2E8F0',
    fontWeight: '600',
  },
  taskTemplate: {
    color: '#64748B',
    fontSize: 12,
  },
  loaderContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
  },
  emptyIcon: {
    fontSize: 40,
    marginBottom: 12,
  },
  emptyTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 4,
  },
  emptySubtitle: {
    color: '#64748B',
    fontSize: 14,
    textAlign: 'center',
  },
});

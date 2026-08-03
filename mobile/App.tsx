import React, { useEffect, useState } from 'react';
import { StyleSheet, View, ActivityIndicator, SafeAreaView, StatusBar } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LoginScreen } from './src/screens/LoginScreen';
import { TaskListScreen } from './src/screens/TaskListScreen';
import { TaskSubmitScreen } from './src/screens/TaskSubmitScreen';
import { NotificationScreen } from './src/screens/NotificationScreen';
import api from './src/services/api';
import { TaskInstance, UserProfile } from './src/types';

export default function App() {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [currentScreen, setCurrentScreen] = useState<'taskList' | 'taskSubmit' | 'notifications'>('taskList');
  const [selectedTask, setSelectedTask] = useState<TaskInstance | null>(null);
  const [unreadCount, setUnreadCount] = useState(0);

  // Check saved session on app startup
  useEffect(() => {
    const bootstrapAsync = async () => {
      try {
        const storedToken = await AsyncStorage.getItem('user_token');
        const storedProfile = await AsyncStorage.getItem('user_profile');

        if (storedToken && storedProfile) {
          setUser(JSON.parse(storedProfile));
          fetchUnreadCount();
        }
      } catch (e) {
        console.error('Failed to restore session:', e);
      } finally {
        setLoading(false);
      }
    };

    bootstrapAsync();
  }, []);

  const fetchUnreadCount = async () => {
    try {
      const response = await api.get('/notifications');
      setUnreadCount(response.data.unread_count || 0);
    } catch (e) {
      console.warn('Failed to fetch unread count:', e);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (e) {
      // Ignore network failure on logout
    }
    await AsyncStorage.removeItem('user_token');
    await AsyncStorage.removeItem('user_profile');
    setUser(null);
    setCurrentScreen('taskList');
    setSelectedTask(null);
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#38BDF8" />
      </View>
    );
  }

  if (!user) {
    return <LoginScreen onLoginSuccess={(profile) => { setUser(profile); fetchUnreadCount(); }} />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {currentScreen === 'taskList' && (
        <TaskListScreen
          onSelectTask={(task) => {
            setSelectedTask(task);
            setCurrentScreen('taskSubmit');
          }}
          onOpenNotifications={() => setCurrentScreen('notifications')}
          onLogout={handleLogout}
          unreadNotificationCount={unreadCount}
        />
      )}

      {currentScreen === 'taskSubmit' && selectedTask && (
        <TaskSubmitScreen
          task={selectedTask}
          onBack={() => {
            setCurrentScreen('taskList');
            setSelectedTask(null);
          }}
          onSubmitSuccess={() => {
            setCurrentScreen('taskList');
            setSelectedTask(null);
            fetchUnreadCount();
          }}
        />
      )}

      {currentScreen === 'notifications' && (
        <NotificationScreen
          onBack={() => setCurrentScreen('taskList')}
          onRefreshCount={fetchUnreadCount}
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  centered: {
    flex: 1,
    backgroundColor: '#0F172A',
    justifyContent: 'center',
    alignItems: 'center',
  },
});

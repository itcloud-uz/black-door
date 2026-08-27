import React, { useState, useEffect } from 'react';
import Login from './pages/Login';
import AdminDashboard from './pages/AdminDashboard';
import EmployeeDashboard from './pages/EmployeeDashboard';
import api from './services/api';

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkSession();
  }, []);

  const checkSession = async () => {
    const token = localStorage.getItem('accessToken');
    if (!token) {
      setLoading(false);
      return;
    }
    try {
      const res = await api.get('/auth/verify-session');
      setUser(res.data.user);
    } catch (err) {
      console.error('Session verification failed:', err);
      localStorage.removeItem('accessToken');
      localStorage.removeItem('refreshToken');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (err) {
      console.error('Logout request error:', err);
    } finally {
      localStorage.removeItem('accessToken');
      localStorage.removeItem('refreshToken');
      setUser(null);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#EEF2F7]">
        <div className="text-center font-bold text-gray-500 tracking-wide animate-pulse">
          Black Door Yuklanmoqda...
        </div>
      </div>
    );
  }

  if (!user) {
    return <Login onLoginSuccess={(loggedInUser) => setUser(loggedInUser)} />;
  }

  return user.role === 'admin' ? (
    <AdminDashboard user={user} onLogout={handleLogout} />
  ) : (
    <EmployeeDashboard user={user} onLogout={handleLogout} />
  );
}

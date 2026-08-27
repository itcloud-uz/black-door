import React, { useState } from 'react';
import api from '../services/api';

export default function Login({ onLoginSuccess }) {
  const [require2FA, setRequire2FA] = useState(false);
  const [tempToken, setTempToken] = useState('');
  const [authCode, setAuthCode] = useState('');
  const [startLink, setStartLink] = useState('');
  const [mockCodeText, setMockCodeText] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Trigger login via Mock credentials for testing
  const handleTestLogin = async (role) => {
    setError('');
    setLoading(true);
    const mockToken = role === 'admin' ? 'mock-google-token-admin' : 'mock-google-token-employee';

    try {
      const res = await api.post('/auth/google-login', { credential: mockToken });
      
      if (res.data.require2FA) {
        localStorage.setItem('tempToken', res.data.tempToken);
        setTempToken(res.data.tempToken);
        setRequire2FA(true);
        
        // Load Telegram link immediately
        await loadTelegramRequest();
      } else {
        completeLogin(res.data);
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  const loadTelegramRequest = async () => {
    try {
      const res = await api.post('/auth/telegram-request');
      setStartLink(res.data.startLink);
      // Expose mock code for local testing convenience
      setMockCodeText(res.data.mockCode);
    } catch (err) {
      setError('Telegram 2FA ulanishida xatolik yuz berdi');
    }
  };

  const handleFetchMockCode = async () => {
    try {
      const res = await api.post('/auth/telegram-mock');
      setAuthCode(res.data.code);
    } catch (err) {
      setError('Mock kod olishda xatolik yuz berdi');
    }
  };

  const handleVerify2FA = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const res = await api.post('/auth/telegram-verify', { code: authCode });
      // Clear temporary token and save production tokens
      localStorage.removeItem('tempToken');
      localStorage.setItem('accessToken', res.data.accessToken);
      localStorage.setItem('refreshToken', res.data.refreshToken);
      
      onLoginSuccess(res.data.user);
    } catch (err) {
      setError(err.response?.data?.error || '2FA Verification failed');
    } finally {
      setLoading(false);
    }
  };

  const completeLogin = (data) => {
    localStorage.setItem('accessToken', data.accessToken);
    localStorage.setItem('refreshToken', data.refreshToken);
    onLoginSuccess(data.user);
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#EEF2F7] px-4">
      <div className="w-full max-w-md p-8 skeuo-convex">
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-[#EEF2F7] skeuo-convex flex items-center justify-center mx-auto mb-4 text-3xl">
            🚪
          </div>
          <h1 className="text-2xl font-black text-[#2D3748] tracking-tight">Black Door</h1>
          <p className="text-gray-500 text-sm mt-1">Moliyaviy Boxgalteriya & Ishlab Chiqarish Tizimi</p>
        </div>

        {error && (
          <div className="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm font-semibold">
            ⚠️ {error}
          </div>
        )}

        {!require2FA ? (
          <div>
            <div className="mb-6 text-center text-sm text-gray-500">
              Tizimga kirish uchun demo loginlar yoki Google hisobingizdan foydalaning.
            </div>

            <div className="flex flex-col gap-4">
              <button
                onClick={() => handleTestLogin('admin')}
                disabled={loading}
                className="w-full py-3.5 skeuo-btn text-[#2D3748] font-bold text-sm hover:text-blue-600 active:scale-95 duration-150"
              >
                📊 Super Admin Panel (Demo)
              </button>

              <button
                onClick={() => handleTestLogin('employee')}
                disabled={loading}
                className="w-full py-3.5 skeuo-btn text-[#2D3748] font-bold text-sm hover:text-green-600 active:scale-95 duration-150"
              >
                📦 Xodim/Omborxona Panel (Demo)
              </button>
            </div>
            
            <div className="mt-8 text-center text-xs text-gray-400">
              Google OAuth login is activated when CLIENT_ID is provided.
            </div>
          </div>
        ) : (
          <form onSubmit={handleVerify2FA}>
            <h2 className="text-lg font-bold text-[#2D3748] mb-2 text-center">Telegram 2FA Tasdiqlash</h2>
            <p className="text-xs text-center text-gray-500 mb-6">
              Botga kirib tasdiqlang yoki sinov uchun quyidagi mock kodlardan foydalaning.
            </p>

            <div className="form-group mb-6">
              <label className="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                Tasdiqlash kodi (6 raqamli)
              </label>
              <input
                type="text"
                maxLength={6}
                value={authCode}
                onChange={(e) => setAuthCode(e.target.value)}
                className="w-full skeuo-input text-center text-xl font-bold tracking-widest text-[#2D3748]"
                placeholder="000000"
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-150 mb-4"
            >
              {loading ? 'Tekshirilmoqda...' : '✅ Kodni Tasdiqlash'}
            </button>

            <div className="flex flex-col gap-3 mt-6 pt-6 border-t border-dashed border-gray-300">
              {startLink && (
                <a
                  href={startLink}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-center py-2 text-xs font-bold text-blue-500 hover:underline"
                >
                  💬 Telegram Bot orqali kod olish
                </a>
              )}

              <button
                type="button"
                onClick={handleFetchMockCode}
                className="py-2.5 text-xs text-gray-600 font-bold skeuo-btn active:scale-95 duration-150"
              >
                🔑 Sinov uchun avtomatik kod to'ldirish
              </button>

              {mockCodeText && (
                <div className="text-center text-xs text-gray-400 mt-2">
                  Zaxira kodi: <span className="font-mono font-bold text-gray-600">{mockCodeText}</span>
                </div>
              )}
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

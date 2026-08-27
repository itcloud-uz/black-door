import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { 
  Warehouse, 
  ArrowDownLeft, 
  ArrowUpRight, 
  MoveRight, 
  AlertTriangle, 
  History, 
  LogOut,
  RefreshCw,
  Settings
} from 'lucide-react';

// Employee Subcomponents
import Overview from '../components/employee/Overview';
import ReceiveGoods from '../components/employee/ReceiveGoods';
import DispatchGoods from '../components/employee/DispatchGoods';
import TransferGoods from '../components/employee/TransferGoods';
import DamageReport from '../components/employee/DamageReport';
import OperationsHistory from '../components/employee/OperationsHistory';

export default function EmployeeDashboard({ user, onLogout }) {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [products, setProducts] = useState([]);
  const [inventory, setInventory] = useState([]);
  const [operations, setOperations] = useState([]);

  // Stats
  const [stats, setStats] = useState({
    todayReceived: 0,
    todayDispatched: 0,
    totalItems: 0
  });

  // Forms
  const [receiveForm, setReceiveForm] = useState({
    product_id: '', quantity: '', to_location: 'A-sektor, 1-tokcha', notes: ''
  });
  
  const [dispatchForm, setDispatchForm] = useState({
    product_id: '', quantity: '', from_location: '', notes: ''
  });

  const [transferForm, setTransferForm] = useState({
    product_id: '', quantity: '', from_location: '', to_location: '', notes: ''
  });

  const [damageForm, setDamageForm] = useState({
    product_id: '', quantity: '', notes: ''
  });

  const [msg, setMsg] = useState({ text: '', type: '' });

  const [settingsForm, setSettingsForm] = useState({ 
    telegram_id: user.telegram_id || '', 
    full_name: user.full_name || '' 
  });

  const handleSaveSettings = async () => {
    try {
      const res = await api.post('/auth/update-settings', settingsForm);
      setMsg({ text: res.data.message, type: 'success' });
      setTimeout(() => setMsg({ text: '', type: '' }), 4000);
      
      user.telegram_id = res.data.user.telegramId;
      user.is_telegram_verified = res.data.user.isTelegramVerified;
      user.full_name = res.data.user.fullName;
    } catch (err) {
      console.error("Error saving settings:", err);
      setMsg({ text: err.response?.data?.error || 'Sozlamalarni saqlashda xatolik', type: 'error' });
      setTimeout(() => setMsg({ text: '', type: '' }), 4000);
    }
  };

  useEffect(() => {
    loadAllData();
  }, [activeTab]);

  const loadAllData = async () => {
    try {
      const invRes = await api.get('/warehouse/employee/inventory');
      setInventory(invRes.data);

      const opsRes = await api.get('/warehouse/employee/operations');
      setOperations(opsRes.data);

      const prodList = invRes.data.map(item => ({
        id: item.product_id,
        name: item.product_name,
        unit_type: item.unit_type,
        location: item.location,
        quantity: item.quantity
      }));
      setProducts(prodList);

      let recQty = 0;
      let dispQty = 0;
      const todayStr = new Date().toISOString().split('T')[0];

      opsRes.data.forEach(op => {
        if (op.created_at.startsWith(todayStr)) {
          const val = parseFloat(op.quantity);
          if (op.operation_type === 'receive') recQty += val;
          if (op.operation_type === 'dispatch') dispQty += val;
        }
      });

      setStats({
        todayReceived: recQty,
        todayDispatched: dispQty,
        totalItems: invRes.data.length
      });

    } catch (err) {
      showNotification('Ma\'lumotlarni yuklashda xatolik yuz berdi', 'error');
    }
  };

  const showNotification = (text, type = 'success') => {
    setMsg({ text, type });
    setTimeout(() => setMsg({ text: '', type: '' }), 4000);
  };

  // Submit Handlers
  const handleReceive = async (e) => {
    e.preventDefault();
    try {
      await api.post('/warehouse/employee/receive', receiveForm);
      showNotification('Mahsulot muvaffaqiyatli qabul qilindi');
      setReceiveForm({ product_id: '', quantity: '', to_location: 'A-sektor, 1-tokcha', notes: '' });
      loadAllData();
    } catch (err) {
      showNotification(err.response?.data?.error || 'Mahsulot qabul qilishda xatolik', 'error');
    }
  };

  const handleDispatch = async (e) => {
    e.preventDefault();
    try {
      await api.post('/warehouse/employee/dispatch', dispatchForm);
      showNotification('Mahsulot muvaffaqiyatli jo\'natildi');
      setDispatchForm({ product_id: '', quantity: '', from_location: '', notes: '' });
      loadAllData();
    } catch (err) {
      showNotification(err.response?.data?.error || 'Mahsulot jo\'natishda xatolik', 'error');
    }
  };

  const handleTransfer = async (e) => {
    e.preventDefault();
    try {
      await api.post('/warehouse/employee/transfer', transferForm);
      showNotification('Mahsulot ombor ichida ko\'chirildi');
      setTransferForm({ product_id: '', quantity: '', from_location: '', to_location: '', notes: '' });
      loadAllData();
    } catch (err) {
      showNotification(err.response?.data?.error || 'Ko\'chirishda xatolik', 'error');
    }
  };

  const handleDamageReport = async (e) => {
    e.preventDefault();
    try {
      await api.post('/warehouse/employee/damage-report', damageForm);
      showNotification('Zararlangan mahsulot dalolatnomasi yuborildi');
      setDamageForm({ product_id: '', quantity: '', notes: '' });
      loadAllData();
    } catch (err) {
      showNotification(err.response?.data?.error || 'Dalolatnoma yuborishda xatolik', 'error');
    }
  };

  return (
    <div className="min-h-screen flex bg-transparent">
      {/* Sidebar Component */}
      <aside className="w-64 skeuo-flat flex flex-col justify-between p-6 border-r border-white/5">
        <div>
          <div className="flex items-center gap-3 mb-8">
            <span className="text-3xl">🏬</span>
            <div>
              <h1 className="text-lg font-black text-slate-100 tracking-tight">Black Door</h1>
              <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Ombor Nazorati</span>
            </div>
          </div>

          <nav className="flex flex-col gap-3">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'dashboard' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Warehouse size={18} /> Bosh sahifa
            </button>
            <button
              onClick={() => setActiveTab('receive')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'receive' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <ArrowDownLeft size={18} /> Qabul qilish
            </button>
            <button
              onClick={() => setActiveTab('dispatch')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'dispatch' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <ArrowUpRight size={18} /> Jo'natish (Savdo)
            </button>
            <button
              onClick={() => setActiveTab('transfer')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'transfer' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <MoveRight size={18} /> Ichki Ko'chirish
            </button>
            <button
              onClick={() => setActiveTab('damage')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'damage' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <AlertTriangle size={18} /> Zarar/Kamomad
            </button>
            <button
              onClick={() => setActiveTab('history')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'history' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <History size={18} /> Hujjatlar tarixi
            </button>
            <button
              onClick={() => {
                setActiveTab('settings');
                setSettingsForm({ telegram_id: user.telegram_id || '', full_name: user.full_name || '' });
              }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'settings' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Settings size={18} /> Sozlamalar
            </button>
          </nav>
        </div>

        <div className="pt-6 border-t border-dashed border-gray-300">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-600 text-sm">
              {user.fullName?.charAt(0)}
            </div>
            <div className="overflow-hidden">
              <div className="text-xs font-bold text-gray-700 truncate">{user.fullName}</div>
              <div className="text-[10px] text-gray-400 truncate">{user.email}</div>
            </div>
          </div>
          <button
            onClick={onLogout}
            className="w-full flex items-center justify-center gap-2 py-2.5 text-xs font-bold text-red-600 skeuo-btn active:scale-95 duration-150"
          >
            <LogOut size={14} /> Chiqish
          </button>
        </div>
      </aside>

      {/* Main Employee content */}
      <main className="flex-1 p-8 overflow-y-auto max-h-screen">
        {msg.text && (
          <div className={`fixed top-6 right-6 px-6 py-4 rounded-xl shadow-xl z-50 text-white font-bold text-sm ${msg.type === 'error' ? 'bg-red-500' : 'bg-green-500'}`}>
            {msg.text}
          </div>
        )}

        {activeTab === 'dashboard' && (
          <Overview
            stats={stats}
            inventory={inventory}
            onRefresh={loadAllData}
          />
        )}

        {activeTab === 'receive' && (
          <ReceiveGoods
            receiveForm={receiveForm}
            setReceiveForm={setReceiveForm}
            products={products}
            onReceive={handleReceive}
          />
        )}

        {activeTab === 'dispatch' && (
          <DispatchGoods
            dispatchForm={dispatchForm}
            setDispatchForm={setDispatchForm}
            products={products}
            onDispatch={handleDispatch}
          />
        )}

        {activeTab === 'transfer' && (
          <TransferGoods
            transferForm={transferForm}
            setTransferForm={setTransferForm}
            products={products}
            onTransfer={handleTransfer}
          />
        )}

        {activeTab === 'damage' && (
          <DamageReport
            damageForm={damageForm}
            setDamageForm={setDamageForm}
            products={products}
            onDamageReport={handleDamageReport}
          />
        )}

        {activeTab === 'history' && (
          <OperationsHistory
            operations={operations}
          />
        )}

        {activeTab === 'settings' && (
          <div className="max-w-xl mx-auto bg-gray-50 rounded-2xl p-6 skeuo-concave border border-gray-200">
            <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
              <Settings className="text-blue-600" /> Akkaunt Sozlamalari
            </h2>
            
            <div className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-gray-500 mb-2">Foydalanuvchi ismi</label>
                <input 
                  type="text"
                  value={settingsForm.full_name}
                  onChange={(e) => setSettingsForm({ ...settingsForm, full_name: e.target.value })}
                  className="w-full px-4 py-3 rounded-xl skeuo-input focus:outline-none"
                />
              </div>

              <div className="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <h3 className="text-sm font-bold text-blue-800 mb-2">Telegram 2FA Ulanishi</h3>
                <p className="text-xs text-blue-600 mb-4 leading-relaxed">
                  Tizimga xavfsiz kirish uchun Telegram botimizga start bosing, u yerda ko'rsatilgan <strong>Chat ID</strong>-ni pastdagi maydonga kiriting va saqlang.
                </p>
                
                <div className="flex gap-4 items-center mb-4">
                  <a 
                    href={`https://t.me/Itcloudvertifikatsiya_bot`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 duration-150"
                  >
                    Botni ishga tushirish (Start)
                  </a>
                  <span className="text-xs text-gray-400">Bot username: @Itcloudvertifikatsiya_bot</span>
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2">Sizning Telegram Chat ID</label>
                  <input 
                    type="text"
                    placeholder="Masalan: 1412501744"
                    value={settingsForm.telegram_id || ''}
                    onChange={(e) => setSettingsForm({ ...settingsForm, telegram_id: e.target.value })}
                    className="w-full px-4 py-3 rounded-xl skeuo-input focus:outline-none font-mono"
                  />
                  {user.is_telegram_verified ? (
                    <span className="inline-block mt-2 text-xs font-bold text-green-600">✓ Telegram 2FA tasdiqlangan va faol</span>
                  ) : (
                    <span className="inline-block mt-2 text-xs font-bold text-amber-600">⚠ Telegram 2FA tasdiqlanmagan</span>
                  )}
                </div>
              </div>

              <button 
                onClick={handleSaveSettings}
                className="w-full py-3.5 rounded-xl text-sm font-bold text-white bg-green-600 active:scale-95 duration-150 hover:bg-green-700 shadow-lg"
              >
                Sozlamalarni saqlash
              </button>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}

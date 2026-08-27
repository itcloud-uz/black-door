import React, { useState, useEffect } from 'react';
import api from '../services/api';
import { 
  LayoutDashboard, 
  DollarSign, 
  Users, 
  Building2, 
  Package, 
  Warehouse, 
  ClipboardList, 
  LogOut,
  RefreshCw,
  Settings
} from 'lucide-react';

// Admin Subcomponents
import Overview from '../components/admin/Overview';
import TransactionManager from '../components/admin/TransactionManager';
import AccountManager from '../components/admin/AccountManager';
import FactoryManager from '../components/admin/FactoryManager';
import ProductCatalog from '../components/admin/ProductCatalog';
import WarehouseViewer from '../components/admin/WarehouseViewer';
import AuditLogViewer from '../components/admin/AuditLogViewer';

export default function AdminDashboard({ user, onLogout }) {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [stats, setStats] = useState({
    totalUzs: 0,
    totalUsd: 0,
    activeFactories: 0,
    productsCount: 0,
    discrepancies: 0,
    lowStock: 0
  });

  // Data registers
  const [transactions, setTransactions] = useState([]);
  const [accounts, setAccounts] = useState([]);
  const [factories, setFactories] = useState([]);
  const [products, setProducts] = useState([]);
  const [warehouseInventory, setWarehouseInventory] = useState([]);
  const [warehouseOperations, setWarehouseOperations] = useState([]);
  const [auditLogs, setAuditLogs] = useState([]);
  const [discrepancyList, setDiscrepancyList] = useState([]);
  const [lowStockList, setLowStockList] = useState([]);

  // Form states
  const [txForm, setTxForm] = useState({
    transaction_type: 'cash_deposit', amount: '', currency: 'USD',
    from_account: '', to_account: '', description: '',
    factory_id: '', product_id: '', person_id: '', status: 'completed'
  });
  
  const [accForm, setAccForm] = useState({
    account_type: 'person', account_holder_name: '', account_number: '',
    phone: '', email: '', current_balance: '0', currency: 'UZS', account_status: 'active'
  });

  const [facForm, setFacForm] = useState({
    name: '', address: '', phone: '', manager_name: '', equipment_type: '',
    rental_rate_per_day: '0', production_commission_percent: '0'
  });

  const [prodForm, setProdForm] = useState({
    name: '', description: '', unit_type: 'kg', base_price: '0',
    cost_price: '0', quantity_in_stock: '0', category: 'Umumiy', manufacturer: ''
  });

  // Modal / Adjust states
  const [selectedAccId, setSelectedAccId] = useState(null);
  const [adjustAmount, setAdjustAmount] = useState('');
  const [adjustDesc, setAdjustDesc] = useState('');
  
  // Notification / Message state
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
      if (activeTab === 'dashboard') {
        const bsRes = await api.get('/admin/reports/balance-sheet');
        const facRes = await api.get('/admin/factories');
        const prodRes = await api.get('/admin/products');
        const alertRes = await api.get('/warehouse/admin/alerts');

        let uzsVal = 0;
        let usdVal = 0;
        bsRes.data.cashBalances.forEach(cb => {
          if (cb.currency === 'UZS') uzsVal = parseFloat(cb.cash_balance);
          if (cb.currency === 'USD') usdVal = parseFloat(cb.cash_balance);
        });

        setStats({
          totalUzs: uzsVal,
          totalUsd: usdVal,
          activeFactories: facRes.data.filter(f => f.is_active).length,
          productsCount: prodRes.data.length,
          discrepancies: alertRes.data.discrepancies?.length || 0,
          lowStock: alertRes.data.lowStock?.length || 0
        });

        setDiscrepancyList(alertRes.data.discrepancies || []);
        setLowStockList(alertRes.data.lowStock || []);
      }

      if (activeTab === 'transactions') {
        const res = await api.get('/admin/transactions');
        setTransactions(res.data);
        const accs = await api.get('/admin/accounts');
        setAccounts(accs.data);
        const facs = await api.get('/admin/factories');
        setFactories(facs.data);
        const prods = await api.get('/admin/products');
        setProducts(prods.data);
      }

      if (activeTab === 'accounts') {
        const res = await api.get('/admin/accounts');
        setAccounts(res.data);
      }

      if (activeTab === 'factories') {
        const res = await api.get('/admin/factories');
        setFactories(res.data);
        const prods = await api.get('/admin/products');
        setProducts(prods.data);
      }

      if (activeTab === 'products') {
        const res = await api.get('/admin/products');
        setProducts(res.data);
      }

      if (activeTab === 'warehouse') {
        const inv = await api.get('/warehouse/admin/inventory');
        setWarehouseInventory(inv.data);
        const ops = await api.get('/warehouse/admin/operations');
        setWarehouseOperations(ops.data);
      }

      if (activeTab === 'audit') {
        const res = await api.get('/admin/audit-log');
        setAuditLogs(res.data);
      }

    } catch (err) {
      showNotification('Ma\'lumotlarni yuklashda xatolik yuz berdi', 'error');
    }
  };

  const showNotification = (text, type = 'success') => {
    setMsg({ text, type });
    setTimeout(() => setMsg({ text: '', type: '' }), 4000);
  };

  // Transaction Actions
  const handleCreateTx = async (e) => {
    e.preventDefault();
    try {
      await api.post('/admin/transactions', txForm);
      showNotification('Tranzaksiya muvaffaqiyatli saqlandi');
      setTxForm({
        transaction_type: 'cash_deposit', amount: '', currency: 'USD',
        from_account: '', to_account: '', description: '',
        factory_id: '', product_id: '', person_id: '', status: 'completed'
      });
      loadAllData();
    } catch (err) {
      showNotification(err.response?.data?.error || 'Xatolik yuz berdi', 'error');
    }
  };

  const handleDeleteTx = async (id) => {
    if (!window.confirm('Tranzaksiyani o\'chirishni xohlaysizmi?')) return;
    try {
      await api.delete(`/admin/transactions/${id}`);
      showNotification('Tranzaksiya o\'chirildi va balanslar tiklandi');
      loadAllData();
    } catch (err) {
      showNotification('Xatolik yuz berdi', 'error');
    }
  };

  // Account Actions
  const handleCreateAcc = async (e) => {
    e.preventDefault();
    try {
      await api.post('/admin/accounts', accForm);
      showNotification('Yangi hisob yaratildi');
      setAccForm({
        account_type: 'person', account_holder_name: '', account_number: '',
        phone: '', email: '', current_balance: '0', currency: 'UZS', account_status: 'active'
      });
      loadAllData();
    } catch (err) {
      showNotification('Hisob yaratishda xatolik', 'error');
    }
  };

  const handleAdjustBalance = async (e) => {
    e.preventDefault();
    try {
      await api.post(`/admin/accounts/${selectedAccId}/adjust-balance`, {
        adjustmentAmount: parseFloat(adjustAmount),
        description: adjustDesc
      });
      showNotification('Balans muvaffaqiyatli to\'g\'rilandi');
      setSelectedAccId(null);
      setAdjustAmount('');
      setAdjustDesc('');
      loadAllData();
    } catch (err) {
      showNotification('Balans to\'g\'rilashda xatolik', 'error');
    }
  };

  // Factory Actions
  const handleCreateFac = async (e) => {
    e.preventDefault();
    try {
      await api.post('/admin/factories', facForm);
      showNotification('Zavod muvaffaqiyatli qo\'shildi');
      setFacForm({
        name: '', address: '', phone: '', manager_name: '', equipment_type: '',
        rental_rate_per_day: '0', production_commission_percent: '0'
      });
      loadAllData();
    } catch (err) {
      showNotification('Zavod qo\'shishda xatolik', 'error');
    }
  };

  // Product Actions
  const handleCreateProd = async (e) => {
    e.preventDefault();
    try {
      await api.post('/admin/products', prodForm);
      showNotification('Mahsulot katalogga qo\'shildi');
      setProdForm({
        name: '', description: '', unit_type: 'kg', base_price: '0',
        cost_price: '0', quantity_in_stock: '0', category: 'Umumiy', manufacturer: ''
      });
      loadAllData();
    } catch (err) {
      showNotification('Mahsulot qo\'shishda xatolik', 'error');
    }
  };

  const handleExport = (format) => {
    window.open(`${api.defaults.baseURL}/admin/transactions/export?format=${format}`, '_blank');
  };

  return (
    <div className="min-h-screen flex bg-transparent">
      {/* Sidebar Component */}
      <aside className="w-64 skeuo-flat flex flex-col justify-between p-6 border-r border-white/5">
        <div>
          <div className="flex items-center gap-3 mb-8">
            <span className="text-3xl">🚪</span>
            <div>
              <h1 className="text-lg font-black text-slate-100 tracking-tight">Black Door</h1>
              <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Admin Control</span>
            </div>
          </div>
 
          <nav className="flex flex-col gap-3">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'dashboard' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <LayoutDashboard size={18} /> Asosiy Ko'rinish
            </button>
            <button
              onClick={() => setActiveTab('transactions')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'transactions' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <DollarSign size={18} /> Tranzaksiyalar
            </button>
            <button
              onClick={() => setActiveTab('accounts')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'accounts' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Users size={18} /> Shaxslar (Kassalar)
            </button>
            <button
              onClick={() => setActiveTab('factories')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'factories' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Building2 size={18} /> Zavodlar
            </button>
            <button
              onClick={() => setActiveTab('products')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'products' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Package size={18} /> Mahsulotlar
            </button>
            <button
              onClick={() => setActiveTab('warehouse')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'warehouse' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <Warehouse size={18} /> Omborxona
            </button>
            <button
              onClick={() => setActiveTab('audit')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'audit' ? 'skeuo-concave text-indigo-400' : 'hover:bg-white/10 text-slate-300'}`}
            >
              <ClipboardList size={18} /> Audit Log
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

      {/* Main Panel Content */}
      <main className="flex-1 p-8 overflow-y-auto max-h-screen">
        {msg.text && (
          <div className={`fixed top-6 right-6 px-6 py-4 rounded-xl shadow-xl z-50 text-white font-bold text-sm ${msg.type === 'error' ? 'bg-red-500' : 'bg-green-500'}`}>
            {msg.text}
          </div>
        )}

        {activeTab === 'dashboard' && (
          <Overview 
            stats={stats} 
            discrepancyList={discrepancyList} 
            lowStockList={lowStockList} 
            onRefresh={loadAllData} 
          />
        )}

        {activeTab === 'transactions' && (
          <TransactionManager
            transactions={transactions}
            accounts={accounts}
            factories={factories}
            products={products}
            txForm={txForm}
            setTxForm={setTxForm}
            onCreateTx={handleCreateTx}
            onDeleteTx={handleDeleteTx}
            onExport={handleExport}
          />
        )}

        {activeTab === 'accounts' && (
          <AccountManager
            accounts={accounts}
            accForm={accForm}
            setAccForm={setAccForm}
            onCreateAcc={handleCreateAcc}
            selectedAccId={selectedAccId}
            setSelectedAccId={setSelectedAccId}
            adjustAmount={adjustAmount}
            setAdjustAmount={setAdjustAmount}
            adjustDesc={adjustDesc}
            setAdjustDesc={setAdjustDesc}
            onAdjustBalance={handleAdjustBalance}
            onRefresh={loadAllData}
          />
        )}

        {activeTab === 'factories' && (
          <FactoryManager
            factories={factories}
            facForm={facForm}
            setFacForm={setFacForm}
            onCreateFac={handleCreateFac}
            onRefresh={loadAllData}
          />
        )}

        {activeTab === 'products' && (
          <ProductCatalog
            products={products}
            prodForm={prodForm}
            setProdForm={setProdForm}
            onCreateProd={handleCreateProd}
            onRefresh={loadAllData}
          />
        )}

        {activeTab === 'warehouse' && (
          <WarehouseViewer
            warehouseInventory={warehouseInventory}
            warehouseOperations={warehouseOperations}
          />
        )}

        {activeTab === 'audit' && (
          <AuditLogViewer
            auditLogs={auditLogs}
          />
        )}

        {activeTab === 'settings' && (
          <div className="max-w-xl mx-auto bg-gray-50 rounded-2xl p-6 skeuo-concave border border-gray-200">
            <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
              <Settings className="text-blue-600" /> Tizim Sozlamalari
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

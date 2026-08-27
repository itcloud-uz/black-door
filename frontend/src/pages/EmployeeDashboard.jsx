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
  RefreshCw
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
    <div className="min-h-screen flex bg-[#EEF2F7]">
      {/* Sidebar Component */}
      <aside className="w-64 skeuo-flat flex flex-col justify-between p-6">
        <div>
          <div className="flex items-center gap-3 mb-8">
            <span className="text-3xl">🏬</span>
            <div>
              <h1 className="text-lg font-black text-[#2D3748] tracking-tight">Black Door</h1>
              <span className="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Ombor Nazorati</span>
            </div>
          </div>

          <nav className="flex flex-col gap-3">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'dashboard' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <Warehouse size={18} /> Bosh sahifa
            </button>
            <button
              onClick={() => setActiveTab('receive')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'receive' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <ArrowDownLeft size={18} /> Qabul qilish
            </button>
            <button
              onClick={() => setActiveTab('dispatch')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'dispatch' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <ArrowUpRight size={18} /> Jo'natish (Savdo)
            </button>
            <button
              onClick={() => setActiveTab('transfer')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'transfer' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <MoveRight size={18} /> Ichki Ko'chirish
            </button>
            <button
              onClick={() => setActiveTab('damage')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'damage' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <AlertTriangle size={18} /> Zarar/Kamomad
            </button>
            <button
              onClick={() => setActiveTab('history')}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold duration-150 ${activeTab === 'history' ? 'skeuo-concave text-blue-600' : 'hover:bg-gray-100 text-gray-600'}`}
            >
              <History size={18} /> Hujjatlar tarixi
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
      </main>
    </div>
  );
}

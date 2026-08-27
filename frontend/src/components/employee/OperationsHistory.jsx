import React from 'react';

export default function OperationsHistory({ operations }) {
  return (
    <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
      <h2 className="text-xl font-black text-slate-100 mb-6">Omborxona Hujjatlar Tarixi</h2>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
              <th className="py-3 px-4">Operatsiya</th>
              <th className="py-3 px-4">Mahsulot</th>
              <th className="py-3 px-4">Miqdori</th>
              <th className="py-3 px-4">From (Qayerdan)</th>
              <th className="py-3 px-4">To (Qayerga)</th>
              <th className="py-3 px-4">Izoh</th>
              <th className="py-3 px-4">Sana / Vaqt</th>
            </tr>
          </thead>
          <tbody>
            {operations.map(op => (
              <tr key={op.id} className="border-b border-white/5 hover:bg-white/5">
                <td className="py-3 px-4">
                  <span className={`px-2.5 py-1 rounded text-[10px] font-bold border ${
                    op.operation_type === 'receive' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                    op.operation_type === 'dispatch' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' :
                    op.operation_type === 'damage_report' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                  }`}>{op.operation_type}</span>
                </td>
                <td className="py-3 px-4 font-bold text-slate-200">{op.product_name}</td>
                <td className="py-3 px-4 font-black text-slate-100">{parseFloat(op.quantity).toLocaleString()} {op.unit_type}</td>
                <td className="py-3 px-4 font-mono text-slate-400">{op.from_location || '—'}</td>
                <td className="py-3 px-4 font-mono text-slate-400">{op.to_location || '—'}</td>
                <td className="py-3 px-4 text-slate-300">{op.notes || '—'}</td>
                <td className="py-3 px-4 text-slate-300">{new Date(op.created_at).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

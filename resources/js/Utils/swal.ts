import Swal from 'sweetalert2';

export const customSwal = Swal.mixin({
  background: '#0f172a', // slate-900
  color: '#f8fafc', // slate-50
  confirmButtonColor: '#0284c7', // sky-600
  cancelButtonColor: '#475569', // slate-600
  customClass: {
    popup: 'border border-slate-800 rounded-3xl shadow-2xl font-sans',
    title: 'text-lg font-bold text-slate-100',
    htmlContainer: 'text-xs text-slate-300',
    confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-lg transition-transform hover:scale-105',
    cancelButton: 'px-5 py-2.5 rounded-xl font-semibold text-xs transition-transform hover:scale-105',
    input: 'bg-slate-950 border border-slate-800 text-slate-100 text-sm rounded-xl focus:border-sky-500',
  },
});

export const confirmDialog = async (title: string, text: string, confirmButtonText = 'Yes, Proceed') => {
  const result = await customSwal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    reverseButtons: true,
  });
  return result.isConfirmed;
};

export const promptDialog = async (title: string, placeholder = '') => {
  const result = await customSwal.fire({
    title,
    input: 'text',
    inputPlaceholder: placeholder,
    showCancelButton: true,
    confirmButtonText: 'Submit',
    cancelButtonText: 'Cancel',
    inputValidator: (value) => {
      if (!value) {
        return 'Please enter a value to proceed.';
      }
    },
  });
  return result.isConfirmed ? result.value : null;
};

export default customSwal;

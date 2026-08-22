/**
 * Locale Toggle — RTL/LTR Support
 *
 * Primary locale: Dari/Persian (RTL) per 02 §3, 03 §11
 * Uses logical CSS properties throughout (ms-/me- not ml-/mr-)
 * dir="rtl" set at document root when Persian is active
 */

import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type Locale = 'en' | 'fa'; // English or Dari/Persian

interface LocaleState {
  locale: Locale;
  dir: 'ltr' | 'rtl';
  setLocale: (locale: Locale) => void;
  toggleLocale: () => void;
  t: (key: string) => string;
}

// Translation strings (subset — expand as needed)
const translations: Record<Locale, Record<string, string>> = {
  en: {
    'app.name': 'TOEFL House',
    'app.tagline': 'Management Information System',
    'nav.dashboard': 'Dashboard',
    'nav.students': 'Students',
    'nav.classes': 'Classes',
    'nav.teachers': 'Teachers',
    'nav.visitors': 'Visitors & Leads',
    'nav.finance': 'Finance',
    'nav.inventory': 'Inventory',
    'nav.funding': 'Funding & Impact',
    'nav.settings': 'Settings',
    'auth.signIn': 'Sign in',
    'auth.signOut': 'Sign out',
    'auth.username': 'Username',
    'auth.password': 'Password',
    'common.search': 'Search...',
    'common.add': 'Add',
    'common.save': 'Save',
    'common.cancel': 'Cancel',
    'common.delete': 'Delete',
    'common.edit': 'Edit',
    'common.view': 'View',
    'common.actions': 'Actions',
    'common.status': 'Status',
    'common.name': 'Name',
    'common.phone': 'Phone',
    'common.active': 'Active',
    'common.inactive': 'Inactive',
    'currency': 'AFN',
  },
  fa: {
    'app.name': 'خانه تافل',
    'app.tagline': 'سیستم مدیریت اطلاعات',
    'nav.dashboard': 'داشبورد',
    'nav.students': 'شاگردان',
    'nav.classes': 'صنف‌ها',
    'nav.teachers': 'استادان',
    'nav.visitors': 'مراجعه‌کنندگان',
    'nav.finance': 'مالی',
    'nav.inventory': 'انبار',
    'nav.funding': 'تمویل و تأثیر',
    'nav.settings': 'تنظیمات',
    'auth.signIn': 'ورود',
    'auth.signOut': 'خروج',
    'auth.username': 'نام کاربری',
    'auth.password': 'رمز عبور',
    'common.search': 'جستجو...',
    'common.add': 'افزودن',
    'common.save': 'ذخیره',
    'common.cancel': 'لغو',
    'common.delete': 'حذف',
    'common.edit': 'ویرایش',
    'common.view': 'مشاهده',
    'common.actions': 'عملیات',
    'common.status': 'وضعیت',
    'common.name': 'نام',
    'common.phone': 'تلفن',
    'common.active': 'فعال',
    'common.inactive': 'غیرفعال',
    'currency': 'افغانی',
  },
};

export const useLocaleStore = create<LocaleState>()(
  persist(
    (set, get) => ({
      locale: 'en',
      dir: 'ltr',

      setLocale: (locale: Locale) => {
        const dir = locale === 'fa' ? 'rtl' : 'ltr';
        document.documentElement.dir = dir;
        document.documentElement.lang = locale === 'fa' ? 'fa' : 'en';
        set({ locale, dir });
      },

      toggleLocale: () => {
        const current = get().locale;
        const next = current === 'en' ? 'fa' : 'en';
        get().setLocale(next);
      },

      t: (key: string) => {
        const locale = get().locale;
        return translations[locale][key] || translations['en'][key] || key;
      },
    }),
    { name: 'toefl-house-locale' }
  )
);

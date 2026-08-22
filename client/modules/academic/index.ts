/**
 * Academic Module - Public Interface
 */

export { useStudents, useStudent, useClasses, useCreateStudent, useEnrollStudent, useCreateClass } from './hooks/useAcademic';
export { StudentsPage } from './components/StudentsPage';
export { ClassesPage } from './components/ClassesPage';
export { AttendanceMarking } from './components/AttendanceMarking';
export { StudentJourneyTimeline } from './components/StudentJourneyTimeline';
export type { Student, AcademicClass, Session, Enrollment } from './types';

import CalendarFeedController from './CalendarFeedController';
import CourseController from './CourseController';
import DashboardController from './DashboardController';
import NotificationController from './NotificationController';
import DocumentController from './DocumentController';
import OrganizationController from './OrganizationController';
import CalendarController from './CalendarController';
import TaskController from './TaskController';
import LifecycleController from './LifecycleController';
import AlumniController from './AlumniController';
import AdminController from './AdminController';
import Settings from './Settings';
const Controllers = {
    CalendarFeedController: Object.assign(
        CalendarFeedController,
        CalendarFeedController,
    ),
    CourseController: Object.assign(CourseController, CourseController),
    DashboardController: Object.assign(
        DashboardController,
        DashboardController,
    ),
    NotificationController: Object.assign(
        NotificationController,
        NotificationController,
    ),
    DocumentController: Object.assign(DocumentController, DocumentController),
    OrganizationController: Object.assign(
        OrganizationController,
        OrganizationController,
    ),
    CalendarController: Object.assign(CalendarController, CalendarController),
    TaskController: Object.assign(TaskController, TaskController),
    LifecycleController: Object.assign(
        LifecycleController,
        LifecycleController,
    ),
    AlumniController: Object.assign(AlumniController, AlumniController),
    AdminController: Object.assign(AdminController, AdminController),
    Settings: Object.assign(Settings, Settings),
};

export default Controllers;

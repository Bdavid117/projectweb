import { defineIcons } from '@animateicons/vanilla';
import { LayoutDashboard } from '@animateicons/vanilla/lucide/layout-dashboard';
import { Users } from '@animateicons/vanilla/lucide/users';
import { FileText } from '@animateicons/vanilla/lucide/file-text';
import { Sparkles } from '@animateicons/vanilla/lucide/sparkles';
import { ChartBar } from '@animateicons/vanilla/lucide/chart-bar';
import { Shield } from '@animateicons/vanilla/lucide/shield';
import { Upload } from '@animateicons/vanilla/lucide/upload';
import { List } from '@animateicons/vanilla/lucide/list';
import { History } from '@animateicons/vanilla/lucide/history';
import { User } from '@animateicons/vanilla/lucide/user';
import { BookOpen } from '@animateicons/vanilla/lucide/book-open';
import { TrendingUp } from '@animateicons/vanilla/lucide/trending-up';
import { Briefcase } from '@animateicons/vanilla/lucide/briefcase';
import { MessageSquare } from '@animateicons/vanilla/lucide/message-square';
import { ClipboardList } from '@animateicons/vanilla/lucide/clipboard-list';
import { Menu } from '@animateicons/vanilla/lucide/menu';
import { Bell } from '@animateicons/vanilla/lucide/bell';
import { ChevronDown } from '@animateicons/vanilla/lucide/chevron-down';
import { LogOut } from '@animateicons/vanilla/lucide/log-out';
import { Calendar } from '@animateicons/vanilla/lucide/calendar';
import { Plus } from '@animateicons/vanilla/lucide/plus';
import { Search } from '@animateicons/vanilla/lucide/search';
import { Mail } from '@animateicons/vanilla/lucide/mail';
import { Eye } from '@animateicons/vanilla/lucide/eye';
import { EyeOff } from '@animateicons/vanilla/lucide/eye-off';
import { Lock } from '@animateicons/vanilla/lucide/lock';
import { X } from '@animateicons/vanilla/lucide/x';
import { CircleCheck } from '@animateicons/vanilla/lucide/circle-check';
import { UserPlus } from '@animateicons/vanilla/lucide/user-plus';
import { FilePlus } from '@animateicons/vanilla/lucide/file-plus';
import { Download } from '@animateicons/vanilla/lucide/download';
import { CloudUpload } from '@animateicons/vanilla/lucide/cloud-upload';
import { TriangleAlert } from '@animateicons/vanilla/lucide/triangle-alert';
import { CircleAlert } from '@animateicons/vanilla/lucide/circle-alert';
import { GraduationCap } from '@animateicons/vanilla/lucide/graduation-cap';
import { Trophy } from '@animateicons/vanilla/lucide/trophy';
import { Mic } from '@animateicons/vanilla/lucide/mic';
import { Plane } from '@animateicons/vanilla/lucide/plane';
import { Star } from '@animateicons/vanilla/lucide/star';
import { Pencil } from '@animateicons/vanilla/lucide/pencil';
import { Trash2 } from '@animateicons/vanilla/lucide/trash-2';
import { Info } from '@animateicons/vanilla/lucide/info';

// Mantener sincronizado con config/animated-icons.php (lo verifica AnimatedIconsTest).
defineIcons({
    LayoutDashboard,
    Users,
    FileText,
    Sparkles,
    ChartBar,
    Shield,
    Upload,
    List,
    History,
    User,
    BookOpen,
    TrendingUp,
    Briefcase,
    MessageSquare,
    ClipboardList,
    Menu,
    Bell,
    ChevronDown,
    LogOut,
    Calendar,
    Plus,
    Search,
    Mail,
    Eye,
    EyeOff,
    Lock,
    X,
    CircleCheck,
    UserPlus,
    FilePlus,
    Download,
    CloudUpload,
    TriangleAlert,
    CircleAlert,
    GraduationCap,
    Trophy,
    Mic,
    Plane,
    Star,
    Pencil,
    Trash2,
    Info,
});

// El ícono se anima cuando el puntero entra en el control que lo contiene (enlace, botón o [data-icon-trigger]).
const host = (event) => event.target.closest?.('a, button, label, [data-icon-trigger]');
const play = (event, method) => {
    const el = host(event);
    if (!el || (event.relatedTarget && el.contains(event.relatedTarget))) return;
    el.querySelectorAll('animate-icons').forEach((icon) => icon[method]?.());
};

document.addEventListener('pointerover', (event) => play(event, 'startAnimation'));
document.addEventListener('pointerout', (event) => play(event, 'stopAnimation'));
document.addEventListener('focusin', (event) => play(event, 'startAnimation'));
document.addEventListener('focusout', (event) => play(event, 'stopAnimation'));

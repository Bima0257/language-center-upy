import {
    IconChartPie,
    IconBooks,
    IconClipboardCheck,
    IconFileDescription,
    IconEyeCheck,
    IconUsers,
    IconReport,
    IconDatabase,
    IconCertificate,
    IconFolders,
    IconCategory,
    IconListDetails,
} from "@tabler/icons-vue";

export function getNav(roles) {
    const isStudent = roles.includes("student");
    const isInstructor = roles.includes("instructor");
    const isAdmin = roles.includes("admin") || roles.includes("superadmin");
    const isProctorOnly = roles.includes("proctor") && !isAdmin;

    const nav = [];

    if (isStudent) {
        nav.push(
            { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
            { label: "Tryout", icon: IconClipboardCheck, route: "exam.available" },
        );
    }

    if (isInstructor) {
        nav.push(
            { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
            { label: "Bank Soal", icon: IconBooks, route: "content-library.index" },
            { label: "Materi Soal", icon: IconFileDescription, route: "content-library.passages.index" },
        );
    }

    if (isProctorOnly) {
        nav.push(
            { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
            { label: "Dashboard Pengawas", icon: IconEyeCheck, route: "proctor.dashboard" },
        );
    }

    if (isAdmin) {
        nav.push(
            { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
            {
                label: "Soal",
                icon: IconBooks,
                children: [
                    { label: "Jenis Tes", icon: IconCategory, route: "admin.master-data.exam-types.index" },
                    { label: "Bank Soal Manager", icon: IconFolders, route: "content-library.question-banks.index" },
                    { label: "Master Skill", icon: IconDatabase, route: "admin.master-data.skills.index" },
                    { label: "Part Soal", icon: IconListDetails, route: "admin.master-data.parts.index" },
                    { label: "Materi Soal", icon: IconFileDescription, route: "content-library.passages.index" },
                    { label: "Bank Soal", icon: IconBooks, route: "content-library.index" },
                ],
            },
            { label: "Manajemen Ujian", icon: IconFileDescription, route: "admin.exams.index" },
            { label: "Verifikasi", icon: IconUsers, route: "admin.verify-users" },
            { label: "Sertifikat", icon: IconCertificate, route: "admin.certificates.index" },
            { label: "Dashboard Pengawas", icon: IconEyeCheck, route: "proctor.dashboard" },
            { label: "Laporan", icon: IconReport, route: "admin.reports.integrity" },
        );
    }

    return nav;
}

function baseOf(route) {
    const segments = route.split(".");
    segments.pop();
    return segments.join(".");
}

function routeMatches(routeName, base) {
    return routeName === base || routeName.startsWith(base + ".");
}

export function getBreadcrumbs(title) {
    const roles = window.__inertia_page?.props?.auth?.roles || [];

    let routeName = null;
    try {
        routeName = window.route()?.current();
    } catch {
        routeName = null;
    }

    const url = window.__inertia_page?.url || "";
    const fallback = (candidate) => !candidate || url.includes(candidate.replace(".", "/"));

    const isActiveRoute = (candidate) => {
        if (!candidate) return false;
        if (routeName) return routeName === candidate;
        return fallback(candidate);
    };

    if (routeName === "dashboard") {
        return [{ label: "Dasbor", route: "dashboard" }];
    }

    const items = [{ label: "Dasbor", route: "dashboard" }];

    let best = null;
    let bestBase = "";

    for (const item of getNav(roles)) {
        if (item.children) {
            for (const child of item.children) {
                const childBase = baseOf(child.route);
                if (isActiveRoute(child.route) || (routeName && routeMatches(routeName, childBase))) {
                    if (childBase.length > bestBase.length) {
                        bestBase = childBase;
                        best = { parent: item, child };
                    }
                }
            }
        } else {
            const base = baseOf(item.route);
            if (isActiveRoute(item.route) || (routeName && routeMatches(routeName, base))) {
                if (base.length > bestBase.length) {
                    bestBase = base;
                    best = { parent: null, child: item };
                }
            }
        }
    }

    if (best) {
        if (best.parent) {
            items.push({ label: best.parent.label });
        }
        items.push({ label: best.child.label, route: best.child.route });
    }

    if (title) {
        items.push({ label: title });
    }

    return items;
}

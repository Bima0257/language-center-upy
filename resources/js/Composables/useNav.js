import {
    IconChartPie,
    IconBooks,
    IconClipboardCheck,
    IconFileDescription,
    IconEyeCheck,
    IconUsers,
    IconReport,
    IconCertificate,
    IconFolders,
    IconCategory,
    IconListDetails,
    IconCalendarEvent,
    IconHistory,
    IconSettings,
    IconBuilding,
    IconSchool,
    IconChartBar,
} from "@tabler/icons-vue";

export function getNav(roles, permissions = []) {
    const isMahasiswa = roles.includes("mahasiswa");
    const isSuperadmin = roles.includes("superadmin");
    const isAdmin = roles.includes("admin") || roles.includes("superadmin");
    const can = (permission) => isSuperadmin || permissions.includes(permission);

    const nav = [];

    if (isMahasiswa) {
        nav.push(
            { label: "Dasbor", icon: IconChartPie, route: "dasbor" },
            { label: "Tryout", icon: IconClipboardCheck, route: "ujian.tersedia", permission: "exam_session.take" },
            { label: "Ujian", icon: IconCalendarEvent, route: "ujian.jadwal", permission: "exam_session.take" },
            { label: "Riwayat Tryout", icon: IconHistory, route: "ujian.riwayat", permission: "exam_session.take" },
            { label: "Sertifikat", icon: IconCertificate, route: "ujian.sertifikat", permission: "exam_session.take" },
        );
    }

    if (isAdmin) {
        nav.push(
            { label: "Dasbor", icon: IconChartPie, route: "dasbor" },
            {
                label: "Soal",
                icon: IconBooks,
                children: [
                    { label: "Bank Soal Manager", icon: IconFolders, route: "pustaka-soal.bank-soal.daftar", permission: "soal.view" },
                    { label: "Bagian Soal", icon: IconListDetails, route: "admin.data-induk.bagian-soal.daftar", permission: "master_data.view" },
                    { label: "Bank Soal", icon: IconBooks, route: "pustaka-soal.daftar", permission: "soal.view" },
                ],
            },
            {
                label: "Master Data",
                icon: IconFolders,
                children: [
                    { label: "Kategori Tes", icon: IconCategory, route: "admin.data-induk.jenis-tes.daftar", permission: "master_data.view" },
                    { label: "Keterampilan", icon: IconListDetails, route: "admin.data-induk.keterampilan.daftar", permission: "master_data.view" },
                    { label: "Fakultas", icon: IconBuilding, route: "admin.data-induk.fakultas.daftar", permission: "master_data.view" },
                    { label: "Jurusan", icon: IconSchool, route: "admin.data-induk.program-studi.daftar", permission: "master_data.view" },
                    { label: "Interpretasi Skor", icon: IconListDetails, route: "admin.data-induk.interpretasi-skor.daftar", permission: "master_data.view" },
                ],
            },
            {
                label: "Manajemen Ujian",
                icon: IconFileDescription,
                children: [
                    { label: "Daftar Ujian", icon: IconClipboardCheck, route: "admin.ujian.daftar", permission: "exam.manage" },
                    { label: "Penjadwalan", icon: IconCalendarEvent, route: "admin.periode-ujian.daftar", permission: "periodeUjian.view" },
                ],
            },
            { label: "Verifikasi", icon: IconUsers, route: "admin.verifikasi-pengguna", permission: "pengguna.view" },
            { label: "Sertifikat", icon: IconCertificate, route: "admin.sertifikat.daftar", permission: "certificate.view" },
            { label: "Dasbor Pengawas", icon: IconEyeCheck, route: "pengawas.dasbor", permission: "exam_session.monitor" },
            { label: "Manajemen Pengguna", icon: IconUsers, route: "admin.pengguna.daftar", permission: "pengguna.view" },
            { label: "Log Aktivitas", icon: IconHistory, route: "admin.catatan-aktivitas.daftar", permission: "audit_log.view" },
            { label: "Analitik", icon: IconChartBar, route: "admin.analitik.daftar", permission: "analytics.view" },
            { label: "Pengaturan", icon: IconSettings, route: "admin.pengaturan.daftar", permission: "settings.manage" },
            { label: "Pratinjau Komponen", icon: IconCategory, route: "admin.komponen.daftar", permission: "components.view" },
            { label: "Laporan", icon: IconReport, route: "admin.laporan.sesi", permission: "report.view" },
        );
    }

    return nav.flatMap((item) => {
        if (item.permission && !can(item.permission)) {
            return [];
        }

        if (!item.children) {
            return [item];
        }

        const children = item.children.filter(
            (child) => !child.permission || can(child.permission),
        );

        return children.length ? [{ ...item, children }] : [];
    });
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
    const permissions = window.__inertia_page?.props?.auth?.permissions || [];

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

    if (routeName === "dasbor") {
        return [{ label: "Dasbor", route: "dasbor" }];
    }

    const items = [{ label: "Dasbor", route: "dasbor" }];

    let best = null;
    let bestBase = "";

    for (const item of getNav(roles, permissions)) {
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

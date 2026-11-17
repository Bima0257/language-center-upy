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
    IconPlayerPlay,
} from "@tabler/icons-vue";

export function getNav(roles, permissions = []) {
    const isPeserta = roles.includes("peserta");
    const isSuperadmin = roles.includes("superadmin");
    const isAdmin = roles.includes("admin") || roles.includes("superadmin");
    const can = (permission) =>
        isSuperadmin || permissions.includes(permission);

    const nav = [];

    if (isPeserta) {
        nav.push(
            { label: "Dasbor", icon: IconChartPie, route: "dasbor" },
            {
                label: "Tryout",
                icon: IconClipboardCheck,
                route: "ujian.tersedia",
                permission: "ujian.tersedia",
            },
            {
                label: "Ujian",
                icon: IconCalendarEvent,
                route: "ujian.jadwal",
                permission: "ujian.jadwal",
            },
            {
                label: "Riwayat Tryout",
                icon: IconHistory,
                route: "ujian.riwayat",
                permission: "ujian.riwayat",
            },
            {
                label: "Sertifikat",
                icon: IconCertificate,
                route: "ujian.sertifikat",
                permission: "ujian.sertifikat",
            },
        );
    }

    if (isAdmin) {
        nav.push(
            { label: "Dasbor", icon: IconChartPie, route: "dasbor" },
            {
                label: "Master Data",
                icon: IconFolders,
                children: [
                    {
                        label: "Program Tes",
                        icon: IconCategory,
                        route: "admin.data-induk.program-tes.daftar",
                        permission: "admin.data-induk.program-tes.daftar",
                    },
                    {
                        label: "Section",
                        icon: IconListDetails,
                        route: "admin.data-induk.section.daftar",
                        permission: "admin.data-induk.section.daftar",
                    },
                    {
                        label: "Fakultas",
                        icon: IconBuilding,
                        route: "admin.data-induk.fakultas.daftar",
                        permission: "admin.data-induk.fakultas.daftar",
                    },
                    {
                        label: "Jurusan",
                        icon: IconSchool,
                        route: "admin.data-induk.program-studi.daftar",
                        permission: "admin.data-induk.program-studi.daftar",
                    },
                    {
                        label: "Interpretasi Skor",
                        icon: IconListDetails,
                        route: "admin.data-induk.interpretasi-skor.daftar",
                        permission: "admin.data-induk.interpretasi-skor.daftar",
                    },
                    {
                        label: "Aturan Penilaian",
                        icon: IconSettings,
                        route: "admin.data-induk.aturan-penilaian.daftar",
                        permission: "admin.data-induk.aturan-penilaian.daftar",
                    },
                ],
            },
            {
                label: "Soal",
                icon: IconBooks,
                children: [
                    {
                        label: "Bank Soal Manager",
                        icon: IconFolders,
                        route: "pustaka-soal.bank-soal.daftar",
                        permission: "pustaka-soal.bank-soal.daftar",
                    },
                    {
                        label: "Part Soal",
                        icon: IconListDetails,
                        route: "admin.data-induk.part-soal.daftar",
                        permission: "admin.data-induk.part-soal.daftar",
                    },
                    {
                        label: "Bank Soal",
                        icon: IconBooks,
                        route: "pustaka-soal.daftar",
                        permission: "pustaka-soal.daftar",
                    },
                ],
            },
            {
                label: "Manajemen Ujian",
                icon: IconFileDescription,
                children: [
                    {
                        label: "Daftar Ujian",
                        icon: IconClipboardCheck,
                        route: "admin.ujian.daftar",
                        permission: "admin.ujian.daftar",
                    },
                    {
                        label: "Demo Ujian",
                        icon: IconPlayerPlay,
                        route: "admin.demo-ujian.daftar",
                        permission: "admin.demo-ujian.daftar",
                    },
                    {
                        label: "Penjadwalan",
                        icon: IconCalendarEvent,
                        route: "admin.periode-ujian.daftar",
                        permission: "admin.periode-ujian.daftar",
                    },
                    {
                        label: "Pendaftaran Ujian",
                        icon: IconUsers,
                        route: "admin.pendaftaran-ujian.daftar",
                        permission: "admin.pendaftaran-ujian.daftar",
                    },
                    {
                        label: "Peserta Ujian",
                        icon: IconUsers,
                        route: "admin.pendaftaran-ujian.peserta-semua",
                        permission: "admin.pendaftaran-ujian.peserta-semua",
                    },
                ],
            },
            {
                label: "Sertifikat",
                icon: IconCertificate,
                route: "admin.sertifikat.daftar",
                permission: "admin.sertifikat.daftar",
            },
            {
                label: "Dasbor Pengawas",
                icon: IconEyeCheck,
                route: "pengawas.dasbor",
                permission: "pengawas.dasbor",
                matchPrefixes: ["pengawas.sesi"],
            },
            {
                label: "Manajemen Pengguna",
                icon: IconUsers,
                children: [
                    {
                        label: "Pengguna",
                        icon: IconUsers,
                        route: "admin.identity.users.index",
                        permission: "admin.identity.users.index",
                    },
                    {
                        label: "Role",
                        icon: IconSettings,
                        route: "admin.identity.roles.index",
                        permission: "admin.identity.roles.index",
                    },
                    {
                        label: "Permission",
                        icon: IconCategory,
                        route: "admin.identity.permissions.index",
                        permission: "admin.identity.permissions.index",
                    },
                ],
            },
            {
                label: "Log Aktivitas",
                icon: IconHistory,
                route: "admin.catatan-aktivitas.daftar",
                permission: "admin.catatan-aktivitas.daftar",
            },
            {
                label: "Analitik",
                icon: IconChartBar,
                route: "admin.analitik.daftar",
                permission: "admin.analitik.daftar",
            },
            {
                label: "Pengaturan",
                icon: IconSettings,
                route: "admin.pengaturan.daftar",
                permission: "admin.pengaturan.daftar",
            },
            {
                label: "Pratinjau Komponen",
                icon: IconCategory,
                route: "admin.komponen.daftar",
                permission: "admin.komponen.daftar",
            },
            {
                label: "Laporan",
                icon: IconReport,
                route: "admin.laporan.sesi",
                permission: "admin.laporan.sesi",
                matchPrefixes: ["admin.laporan.sesi"],
            },
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

export function findActiveNavItem(nav, currentRouteName) {
    if (!currentRouteName) return null;

    let best = null;
    let bestScore = -1;

    const consider = (item, parent = null) => {
        if (!item?.route || item.route === "#") return;

        let score = -1;

        if (currentRouteName === item.route) {
            score = 100000 + item.route.length;
        } else {
            const prefixes = [];

            if (item.route.endsWith(".daftar") || item.route.endsWith(".index")) {
                prefixes.push(baseOf(item.route));
            }

            if (Array.isArray(item.matchPrefixes)) {
                prefixes.push(...item.matchPrefixes);
            }

            for (const prefix of prefixes) {
                if (routeMatches(currentRouteName, prefix)) {
                    score = Math.max(score, prefix.length);
                }
            }
        }

        if (score > bestScore) {
            bestScore = score;
            best = { item, parent, score };
        }
    };

    for (const item of nav) {
        if (item.children) {
            for (const child of item.children) {
                consider(child, item);
            }
        } else {
            consider(item);
        }
    }

    return best;
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
    const fallback = (candidate) =>
        !candidate || url.includes(candidate.replace(".", "/"));

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
                if (
                    isActiveRoute(child.route) ||
                    (routeName && routeMatches(routeName, childBase))
                ) {
                    if (childBase.length > bestBase.length) {
                        bestBase = childBase;
                        best = { parent: item, child };
                    }
                }
            }
        } else {
            const base = baseOf(item.route);
            if (
                isActiveRoute(item.route) ||
                (routeName && routeMatches(routeName, base))
            ) {
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

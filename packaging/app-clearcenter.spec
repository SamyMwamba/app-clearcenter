
Name: app-clearcenter
Epoch: 1
Version: 2.4.3
Release: 1%{dist}
Summary: ClearCenter Base
License: Proprietary
Group: Applications/Apps
Packager: ClearCenter
Vendor: ClearCenter
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: theme-clearos-admin >= 7.4.3

%description
The base system provides a core set of tools for software from ClearCenter.

%package core
Summary: ClearCenter Base - API
License: Proprietary
Group: Applications/API
Requires: app-base-core
Requires: app-base-core >= 1:2.4.24
Requires: app-network-core >= 1:2.3.27
Requires: app-language-core
Requires: app-suva-core
Requires: app-tasks-core >= 1:2.4.0
Requires: csplugin-audit
Requires: yum-marketplace-plugin

%description core
The base system provides a core set of tools for software from ClearCenter.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/clearcenter
cp -r * %{buildroot}/usr/clearos/apps/clearcenter/

install -d -m 0755 %{buildroot}/var/clearos/clearcenter
install -d -m 0755 %{buildroot}/var/clearos/clearcenter/apps
install -d -m 0755 %{buildroot}/var/clearos/clearcenter/subscriptions
install -D -m 0755 packaging/clearcenter-subscriptions %{buildroot}/usr/sbin/clearcenter-subscriptions
install -D -m 0755 packaging/clearcenter-update %{buildroot}/usr/sbin/clearcenter-update
install -D -m 0644 packaging/clearos-fast-updates.repo %{buildroot}/etc/yum.repos.d/clearos-fast-updates.repo
install -D -m 0644 packaging/clearos-gpg-key %{buildroot}/etc/pki/rpm-gpg/clearos-gpg-key
install -D -m 0755 packaging/marketplace_version_ctl.sh %{buildroot}/usr/sbin/marketplace_version_ctl.sh

%post
logger -p local6.notice -t installer 'app-clearcenter - installing'

%post core
logger -p local6.notice -t installer 'app-clearcenter-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/clearcenter/deploy/install ] && /usr/clearos/apps/clearcenter/deploy/install
fi

[ -x /usr/clearos/apps/clearcenter/deploy/upgrade ] && /usr/clearos/apps/clearcenter/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-clearcenter - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-clearcenter-core - uninstalling'
    [ -x /usr/clearos/apps/clearcenter/deploy/uninstall ] && /usr/clearos/apps/clearcenter/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/clearcenter/htdocs

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/clearcenter/packaging
%exclude /usr/clearos/apps/clearcenter/unify.json
%dir /usr/clearos/apps/clearcenter
%dir /var/clearos/clearcenter
%dir /var/clearos/clearcenter/apps
%dir /var/clearos/clearcenter/subscriptions
/usr/clearos/apps/clearcenter/deploy
/usr/clearos/apps/clearcenter/language
/usr/clearos/apps/clearcenter/libraries
/usr/sbin/clearcenter-subscriptions
/usr/sbin/clearcenter-update
/etc/yum.repos.d/clearos-fast-updates.repo
/etc/pki/rpm-gpg/clearos-gpg-key
/usr/sbin/marketplace_version_ctl.sh

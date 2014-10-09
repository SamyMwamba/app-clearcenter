
Name: app-clearcenter
Epoch: 1
Version: 2.0.1
Release: 1%{dist}
Summary: ClearCenter Base
License: Proprietary
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The base system provides a core set of tools for software from ClearCenter.

%package core
Summary: ClearCenter Base - Core
License: Proprietary
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-base-core >= 1:1.5.31
Requires: app-edition
Requires: app-language-core
Requires: app-suva-core
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
install -D -m 0644 packaging/clearos-gpg-key %{buildroot}/etc/pki/rpm-gpg/clearos-gpg-key
install -D -m 0644 packaging/license.ini %{buildroot}/usr/clearos/sandbox/etc/php.d/license.ini
install -D -m 0644 packaging/license.zl %{buildroot}/var/clearos/clearcenter/license.zl
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
%dir /usr/clearos/apps/clearcenter
%dir /var/clearos/clearcenter
%dir /var/clearos/clearcenter/apps
%dir /var/clearos/clearcenter/subscriptions
/usr/clearos/apps/clearcenter/deploy
/usr/clearos/apps/clearcenter/language
/usr/clearos/apps/clearcenter/libraries
/usr/sbin/clearcenter-subscriptions
/usr/sbin/clearcenter-update
/etc/pki/rpm-gpg/clearos-gpg-key
/usr/clearos/sandbox/etc/php.d/license.ini
/var/clearos/clearcenter/license.zl
/usr/sbin/marketplace_version_ctl.sh


Name: app-clearcenter
Epoch: 1
Version: 1.0.16
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
Summary: ClearCenter Base - APIs and install
License: Proprietary
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-language-core
Requires: app-suva-core
Requires: webconfig-zend-guard-loader

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
install -D -m 0755 packaging/clearcenter-update %{buildroot}/usr/sbin/clearcenter-update
install -D -m 0644 packaging/clearos-gpg-key %{buildroot}/etc/pki/rpm-gpg/clearos-gpg-key
install -D -m 0644 packaging/license.ini %{buildroot}/usr/clearos/sandbox/etc/php.d/license.ini
install -D -m 0644 packaging/license.zl %{buildroot}/var/clearos/clearcenter/license.zl
install -D -m 0755 packaging/marketplace_version_ctl.sh %{buildroot}/usr/sbin/marketplace_version_ctl.sh

if [ -d %{buildroot}/usr/clearos/apps/clearcenter/libraries_zendguard ]; then
    rm -rf %{buildroot}/usr/clearos/apps/clearcenter/libraries
    mv %{buildroot}/usr/clearos/apps/clearcenter/libraries_zendguard %{buildroot}/usr/clearos/apps/clearcenter/libraries
fi

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
/usr/clearos/apps/clearcenter/controllers
/usr/clearos/apps/clearcenter/htdocs
/usr/clearos/apps/clearcenter/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/clearcenter/packaging
%exclude /usr/clearos/apps/clearcenter/tests
%dir /usr/clearos/apps/clearcenter
%dir /var/clearos/clearcenter
/usr/clearos/apps/clearcenter/deploy
/usr/clearos/apps/clearcenter/language
/usr/clearos/apps/clearcenter/libraries
/usr/sbin/clearcenter-update
/etc/pki/rpm-gpg/clearos-gpg-key
/usr/clearos/sandbox/etc/php.d/license.ini
/var/clearos/clearcenter/license.zl
/usr/sbin/marketplace_version_ctl.sh

import app from 'flarum/admin/app';
import extenders from './extend';
import PickemPage from './components/PickemPage';

app.initializers.add('bigreja/bragalotto', () => {
  extenders.forEach(extender => extender.extend(app));

  app.extensionData
    .for('bigreja-bragalotto')
    .registerPage(PickemPage)
    .registerPermission({
      icon: 'fas fa-trophy',
      label: app.translator.trans('bigreja-bragalotto.admin.permissions.manage'),
      permission: 'bragalotto.manage'
    }, 'moderate')
    .registerPermission({
      icon: 'fas fa-check-circle',
      label: app.translator.trans('bigreja-bragalotto.admin.permissions.make_picks'),
      permission: 'bragalotto.makePicks'
    }, 'start')
    .registerPermission({
      icon: 'fas fa-eye',
      label: app.translator.trans('bigreja-bragalotto.admin.permissions.view_page'),
      permission: 'bragalotto.view',
	  allowGuest: true 
    }, 'view');
});
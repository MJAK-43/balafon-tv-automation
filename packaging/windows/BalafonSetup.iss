#define MyAppName "Balafon Broadcast Manager"
#define MyAppVersion "0.1.0-rc.1"
#define MyAppPublisher "Balafon"
#define MyAppExeName "cmd.exe"

[Setup]
AppId={{C3F34E66-94D1-45A7-A8AC-D80B4C243B9B}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
VersionInfoVersion=0.1.0.1
AppPublisher={#MyAppPublisher}
DefaultDirName={localappdata}\Programs\Balafon
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
ArchitecturesAllowed=x64compatible
OutputDir=..\..\dist\installer
OutputBaseFilename=Balafon-Setup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
SetupLogging=yes

[Files]
Source: "..\..\dist\windows-release\Balafon\*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs ignoreversion

[Tasks]
Name: "desktopicon"; Description: "Créer un raccourci sur le Bureau"; GroupDescription: "Raccourcis supplémentaires :"; Flags: unchecked

[Icons]
Name: "{autoprograms}\Balafon\Configurer ou réparer Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\install_windows_tester.cmd"""
Name: "{autoprograms}\Balafon\Start Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\start_windows_local.cmd"""
Name: "{autoprograms}\Balafon\Stop Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\stop_windows_local.cmd"""
Name: "{autodesktop}\Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\start_windows_local.cmd"""; Tasks: desktopicon

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\install_windows_tester.ps1"" -MediaRoot ""{localappdata}\Balafon\Media"""; StatusMsg: "Configuration de Balafon..."; Flags: runhidden waituntilterminated
Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\start_windows_local.cmd"""; Description: "Lancer Balafon Broadcast Manager"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\stop_windows_local.ps1"""; Flags: runhidden waituntilterminated skipifdoesntexist; RunOnceId: "StopBalafon"

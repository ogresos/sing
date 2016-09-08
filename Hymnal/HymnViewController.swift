//
//  SecondViewController.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/14/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit
import CoreData
import AVFoundation
import QuartzCore

class HymnViewController: UIViewController, UICollectionViewDataSource, UICollectionViewDelegate, NSFetchedResultsControllerDelegate, AVAudioPlayerDelegate, MJNIndexViewDataSource {
    
//    @IBOutlet weak var self.collectionView: UICollectionView!

    let appDelegate = UIApplication.shared.delegate as! AppDelegate
    var managedObjectContext: NSManagedObjectContext? = nil
    
    /// soundbanks are either dls or sf2. see http://www.sf2midi.com/
    var soundbank:URL!
    var mp:AVMIDIPlayer!
    var ap:AVAudioPlayer!
    
    @IBOutlet weak var collectionView: UICollectionView!
    @IBOutlet weak var playPauseButton: UIBarButtonItem!
    var indexView: MJNIndexView!
    var indexArray = [String]()
    let highestNumber = 1400
    let maxIndexes = 60

    
    var hymns = [NSManagedObject]()
    var theHymn: NSManagedObject!
    var selectedIndexPath: IndexPath = [0, 2]
    var initialScrollDone: Bool = false

    
    

    override func viewDidLoad() {
        super.viewDidLoad()
        
        let collectionViewLayout: HymnFlowLayout = HymnFlowLayout()
        collectionViewLayout.sectionInset = UIEdgeInsets(top: 0, left: 0, bottom: 0, right: 0)
        collectionViewLayout.minimumInteritemSpacing = 0
        collectionViewLayout.minimumLineSpacing = 0
        collectionViewLayout.scrollDirection = UICollectionViewScrollDirection.horizontal
        collectionView!.setCollectionViewLayout(collectionViewLayout, animated: false)

        managedObjectContext = appDelegate.managedObjectContext
        let managedContext = self.fetchedResultsController.managedObjectContext
        let fetchRequest = NSFetchRequest<NSFetchRequestResult>(entityName: "Hymn")
        let sortDescriptors = [NSSortDescriptor(key: "number", ascending:true, selector: #selector(NSString.localizedStandardCompare))]
        fetchRequest.sortDescriptors = sortDescriptors
        
        do {
            let results =
                try managedContext.fetch(fetchRequest)
            hymns = results as! [NSManagedObject]

        } catch let error as NSError {
            print("Could not fetch \(error), \(error.userInfo)")
        }
        
        // set up MIDI file
        setupMIDIFile()
        // set up Audio file
        setupAudioFile()
        
        setupIndexView()

    }
    
    override func viewWillAppear(_ animated: Bool) {
        self.navigationController?.isNavigationBarHidden = true
    }
    
    override func viewWillDisappear(_ animated: Bool) {
        self.navigationController?.isNavigationBarHidden = false
    }
    
    func setupIndexView() {
        let increment = 10 * Int(round(Double(highestNumber/maxIndexes)/10.0))
        var number = increment
        
        
        while number <= highestNumber {
            indexArray.append(String(number))
            number += increment
        }
        print("highestNumber", highestNumber, "maxIndexes", maxIndexes, "increment is", increment, "indexArray", indexArray)

        indexView = MJNIndexView(frame: self.view.bounds)
        indexView.dataSource = self
        indexView.fontColor = UIColor.lightGray
        indexView.font = UIFont(name: "Avenir Next", size:4.0)
        indexView.selectedItemFont = UIFont(name: "Avenir-Heavy", size: 34.0)
        indexView.selectedItemFontColor = UIColor(red: 126.0/255.0, green: 211.0/255.0, blue: 33.0/255.0, alpha: 1.0)
        indexView.darkening = false
        indexView.fading = true
        indexView.curtainColor = UIColor(colorLiteralRed: 255.0/255.0, green: 255.0/255.0, blue: 255.0/255.0, alpha: 0.8)
        indexView.curtainStays = false
        indexView.curtainMoves = true
        indexView.maxItemDeflection = 100.0
        indexView.rangeOfDeflection = 7
        indexView.upperMargin = 20.0
        indexView.lowerMargin = 50.0
        indexView.curtainFade = 0.5
        indexView.ergonomicHeight = false
        self.view.addSubview(indexView)
        self.view.bringSubview(toFront: indexView)
    }
    
    func sectionIndexTitles(for indexView: MJNIndexView!) -> [Any]! {
        return indexArray
    }
    func section(forSectionMJNIndexTitle title: String!, at index: Int) {
        let hymnNumber = indexArray[index]
        let indexPath: IndexPath = [0, indexForHymn(number:hymnNumber)]
        print("scroll to index", indexPath)
        collectionView!.scrollToItem(at: indexPath, at: UICollectionViewScrollPosition.centeredHorizontally, animated: false)
        //collectionView!.scrollToItem(at: selectedIndexPath, at: UICollectionViewScrollPosition.centeredHorizontally, animated: false)
    }

    
    func setupMIDIFile() {
        self.soundbank = Bundle.main.url(forResource: "GeneralUser GS MuseScore v1.442", withExtension: "sf2")
        //self.soundbank = Bundle.main.url(forResource: "FluidR3Mono_GM", withExtension: "sf3")
        // a standard MIDI file.
        let path = Bundle.main.path(forResource:"e0001_i", ofType: "mid")
        let contents = NSURL(fileURLWithPath: path!) as URL
        do {
            //            let music = try AVAudioPlayer(contentsOf: url)
            self.mp = try AVMIDIPlayer(contentsOf: contents, soundBankURL: soundbank)
            self.mp.prepareToPlay()
            
        } catch {
            // couldn't load file
            print("Could not load audio file", contents)
        }
    }
    
    func setupAudioFile() {
        // a standard MIDI file.
        let path = Bundle.main.path(forResource:"e0001_i", ofType: "mp3")
        let contents = NSURL(fileURLWithPath: path!) as URL

        do {
            self.ap = try AVAudioPlayer(contentsOf: contents)
            ap.delegate = self
            ap.prepareToPlay()
            ap.volume = 1.0
        } catch {
            print("Could not load audio file", contents)
        }
    }
    
    @IBAction func toggleMIDIPlayer() {
        if mp.isPlaying {
            mp.stop()
            playPauseButton.image = UIImage(named:"PlayButton")
        } else {
            self.mp.play(nil)
            playPauseButton.image = UIImage(named:"PauseButton")
        }
    }
    
    @IBAction func toggleAudioPlayer() {
        if ap.isPlaying {
            ap.stop()
            playPauseButton.image = UIImage(named:"PlayButton")
        } else {
            self.ap.play()
            playPauseButton.image = UIImage(named:"PauseButton")
        }
    }
    
    
    
    
    override func viewDidLayoutSubviews() {

        
        super.viewDidLayoutSubviews()
        
        if(!initialScrollDone) {
            // Find the indexPath for the selected hymn

            let row = indexForHymn(number: (theHymn as! Hymn).number!)
            selectedIndexPath = [0, row]
            print("loading hymn at index", selectedIndexPath)
            initialScrollDone = true
            self.view.layoutIfNeeded()
            collectionView!.scrollToItem(at: selectedIndexPath, at: UICollectionViewScrollPosition.centeredHorizontally, animated: false)
        }
        else {
            self.collectionView?.decelerationRate = UIScrollViewDecelerationRateFast;
        }
        

    }
    
    
    func indexForHymn(number: String) -> Int {
        print("indexForHymn", number)
        for hymn in hymns {
            if ((hymn as! Hymn).number == number) {
                let index = hymns.index(of: hymn)!
                print("is ", index)
                return index
            }
        }
        return -1
    }
    

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
    // Transition from hymn to index
    
    @IBAction func transitionToIndex(sender: AnyObject?) {
        self.performSegue(withIdentifier: "unwindToIndex", sender: sender)
    }
    
    
    // UICollectionView methods
    
    // MARK: UICollectionViewDataSource
    
    func numberOfSections(in collectionView: UICollectionView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }
    
    
    func collectionView(_ collectionView: UICollectionView, numberOfItemsInSection section: Int) -> Int {
        return hymns.count
    }
    
    

    
    func collectionView(_ collectionView: UICollectionView, layout collectionViewLayout: UICollectionViewLayout, sizeForItemAtIndexPath indexPath: NSIndexPath) -> CGSize {
        let size = CGSize(width: collectionView.bounds.size.width, height: collectionView.bounds.size.height)
        return size
    }
    
//    interspacing
//    func collectionView(_ collectionView: UICollectionView,
//                        layout collectionViewLayout: UICollectionViewLayout,
//                        minimumInteritemSpacingForSectionAt section: Int) -> CGFloat {
//        return 0.0
//    }
//    
//    func collectionView(_ collectionView: UICollectionView, layout
//        collectionViewLayout: UICollectionViewLayout,
//                        minimumLineSpacingForSectionAt section: Int) -> CGFloat {
//        //        let feed = entries[indexPath.row]
//        //        let nextFeed = entries[indexPath.row+1]
//        //        if (indexPath.row == 1) {
//        //            return 100
//        //        }
//        return 0.0
//    }
    
    func collectionView(_ collectionView: UICollectionView, cellForItemAt indexPath: IndexPath) -> UICollectionViewCell {
        let cell = self.collectionView?.dequeueReusableCell(withReuseIdentifier: "HymnCell", for: indexPath) as! HymnCollectionViewCell
        cell.initWith(theHymn: hymns[indexPath.row])
        
        return cell
    }
    
    
    
    
    
    
    // MARK: UICollectionViewDelegate
    
    /*
     // Uncomment this method to specify if the specified item should be highlighted during tracking
     override func collectionView(collectionView: UICollectionView, shouldHighlightItemAtIndexPath indexPath: NSIndexPath) -> Bool {
     return true
     }
     */
    
    /*
     // Uncomment this method to specify if the specified item should be selected
     override func collectionView(collectionView: UICollectionView, shouldSelectItemAtIndexPath indexPath: NSIndexPath) -> Bool {
     return true
     }
     */
    
    /*
     // Uncomment these methods to specify if an action menu should be displayed for the specified item, and react to actions performed on the item
     override func collectionView(collectionView: UICollectionView, shouldShowMenuForItemAtIndexPath indexPath: NSIndexPath) -> Bool {
     return false
     }
     
     override func collectionView(collectionView: UICollectionView, canPerformAction action: Selector, forItemAtIndexPath indexPath: NSIndexPath, withSender sender: AnyObject?) -> Bool {
     return false
     }
     
     override func collectionView(collectionView: UICollectionView, performAction action: Selector, forItemAtIndexPath indexPath: NSIndexPath, withSender sender: AnyObject?) {
     
     }
     */
    
    
    // MARK: - Fetched results controller
    
    var fetchedResultsController: NSFetchedResultsController<Hymn> {
        if _fetchedResultsController != nil {
            return _fetchedResultsController!
        }
        
        let fetchRequest: NSFetchRequest<Hymn> = Hymn.fetchRequest()
        
        // Set the batch size to a suitable number.
        fetchRequest.fetchBatchSize = 3000
        
        // Edit the sort key as appropriate.
        let sortDescriptor = NSSortDescriptor(key: "number", ascending: true)
        
        fetchRequest.sortDescriptors = [sortDescriptor]
        
        // Edit the section name key path and cache name if appropriate.
        // nil for section name key path means "no sections".
        let aFetchedResultsController = NSFetchedResultsController(fetchRequest: fetchRequest, managedObjectContext: self.managedObjectContext!, sectionNameKeyPath: nil, cacheName: "HymnViewController")
        aFetchedResultsController.delegate = self
        _fetchedResultsController = aFetchedResultsController
        
        do {
            try _fetchedResultsController!.performFetch()
        } catch {
            // Replace this implementation with code to handle the error appropriately.
            // fatalError() causes the application to generate a crash log and terminate. You should not use this function in a shipping application, although it may be useful during development.
            let nserror = error as NSError
            fatalError("Unresolved error \(nserror), \(nserror.userInfo)")
        }
        
        return _fetchedResultsController!
    }
    var _fetchedResultsController: NSFetchedResultsController<Hymn>? = nil

}

